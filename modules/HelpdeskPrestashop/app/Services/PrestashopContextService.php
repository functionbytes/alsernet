<?php

namespace Modules\HelpdeskPrestashop\Services;

use App\Helpers\PiiMasker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Helpdesk\Concerns\HasCircuitBreaker;
use Modules\Helpdesk\Models\Customer as HelpdeskCustomer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Jobs\RefreshPsContextJob;
use Modules\HelpdeskPrestashop\Support\HmacSigner;

class PrestashopContextService
{
    use HasCircuitBreaker;

    private const EMPTY_CONTEXT = [
        'customer' => ['found' => false],
        'orders' => [],
        'carts' => [],
    ];

    private const CIRCUIT_KEY = 'helpdeskprestashop:circuit_failures';

    private const CIRCUIT_CONFIG_PREFIX = 'helpdeskprestashop';

    /**
     * $helpdeskCustomerId/$externalId siguen el mismo patrón que
     * ErpContextService::getCustomerContext(): $externalId permite resolver
     * por id_customer de PrestaShop ya conocido en vez de solo por email (el
     * caso de HelpdeskContacts, donde el email del contacto de Helpdesk no
     * tiene por qué coincidir con el de su cuenta de PrestaShop — llegó por
     * WhatsApp/teléfono y se vinculó a mano). Si se encuentra cliente y se
     * pasó $helpdeskCustomerId, el vínculo se persiste automáticamente.
     */
    public function getCustomerContext(string $email, ?int $helpdeskCustomerId = null, ?int $externalId = null): array
    {
        try {
            $result = $this->getCustomerContextOrFail($email, $helpdeskCustomerId, $externalId);
        } catch (PsUpstreamException $e) {
            Log::warning('HelpdeskPrestashop: upstream no disponible, se devuelve contexto vacío.', [
                'email' => PiiMasker::email($email),
                'error' => $e->getMessage(),
            ]);

            return self::EMPTY_CONTEXT;
        }

        unset($result['fetched_at']);

        return $result;
    }

    /**
     * Igual que getCustomerContext(), pero distingue "el puente no responde"
     * de "el cliente no existe en PrestaShop": en el primer caso lanza la
     * excepción en vez de devolver un contexto vacío que el panel pintaría
     * como "Sin pedidos · datos actualizados".
     *
     * Añade 'fetched_at' (unix) con el momento real de la lectura, para el
     * indicador de frescura del tab Tienda.
     *
     * @return array<string, mixed>
     *
     * @throws PsUpstreamException
     */
    public function getCustomerContextOrFail(string $email, ?int $helpdeskCustomerId = null, ?int $externalId = null): array
    {
        $email = $this->normalizeEmail($email);
        $key = $this->cacheKey($email);
        $cached = Cache::get($key);

        if ($cached !== null) {
            $this->maybeRevalidate($email, $cached);

            $cached['fetched_at'] = $cached['_cached_at'] ?? time();
            unset($cached['_cached_at'], $cached['_ttl']);

            return $cached;
        }

        $upstreamFailed = false;
        $result = $this->fetchContext($email, $externalId, $upstreamFailed);

        if ($result === null || ! ($result['customer']['found'] ?? false)) {
            if ($upstreamFailed) {
                throw new PsUpstreamException('PrestaShop no respondió al pedir el contexto del cliente.');
            }

            if ($result === null) {
                return self::EMPTY_CONTEXT + ['fetched_at' => time()];
            }
        }

        $this->putInCache($key, $result);

        // El 'id' puede faltar: cuando customer.helpdesk_context falla y el
        // contexto se reconstruye desde customer.orders (ver fetchContext()),
        // el 'customer' sintetizado trae found=true pero sin 'id'.
        if ($helpdeskCustomerId !== null && ($result['customer']['found'] ?? false) && isset($result['customer']['id'])) {
            $this->persistPrestashopLink($helpdeskCustomerId, (int) $result['customer']['id']);
        }

        $result['fetched_at'] = time();

        return $result;
    }

    /**
     * Persiste automáticamente el vínculo prestashop→cliente Helpdesk cuando
     * getCustomerContext() lo encuentra por email — mismo patrón que
     * ErpContextService::persistErpLink().
     */
    private function persistPrestashopLink(int $helpdeskCustomerId, int $prestashopCustomerId): void
    {
        try {
            HelpdeskCustomer::on('helpdesk')->find($helpdeskCustomerId)
                ?->linkExternalId('prestashop', (string) $prestashopCustomerId, [
                    'linked_at' => now()->toIso8601String(),
                    'linked_by' => 'auto',
                ]);
        } catch (\Throwable $e) {
            Log::warning('HelpdeskPrestashop: no se pudo persistir el vínculo automático.', [
                'helpdesk_customer_id' => $helpdeskCustomerId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Refresca la caché del cliente con un único Cache::put atómico.
     * Si la API falla, NO toca la caché (mantiene la entrada anterior si existe).
     */
    public function revalidate(string $email): void
    {
        $email = $this->normalizeEmail($email);

        try {
            $result = $this->fetchContext($email);
        } catch (PsUpstreamException) {
            // Si el upstream no está disponible, mantener el dato en caché tal como está.
            return;
        }

        if ($result === null) {
            return;
        }

        $this->putInCache($this->cacheKey($email), $result);
    }

    public function forgetCache(string $email): void
    {
        Cache::forget($this->cacheKey($this->normalizeEmail($email)));
    }

    /**
     * Lectura pura de la caché del contexto: nunca hace HTTP ni programa un
     * refresco (a diferencia de getCustomerContext()). Para llamadores que van
     * por lote (listado de Contactos) y no pueden pagar la latencia del bridge.
     * Devuelve null si el cliente aún no está cacheado (TTL cache_ttl).
     *
     * @return array<string, mixed>|null
     */
    public function peekCachedContext(string $email): ?array
    {
        $cached = Cache::get($this->cacheKey($this->normalizeEmail($email)));

        if (! is_array($cached)) {
            return null;
        }

        unset($cached['_cached_at'], $cached['_ttl']);

        return $cached;
    }

    public function getOrderDetail(int $orderId, ?string $customerEmail = null, ?int $externalId = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'order.detail', $orderId);
        if ($lookup === null) {
            return null;
        }

        return $this->callApi('order.detail', [
            'order_id' => $orderId,
            'lookup' => $lookup,
        ]);
    }

    /**
     * Variante de getOrderDetail() SIN el chequeo de propiedad por email —
     * consulta el pedido solo por su id, sin verificar a qué cliente
     * pertenece. Existe para llamadores que aún no conocen el email del
     * cliente y necesitan descubrirlo a partir del order_id (p. ej. el
     * módulo Document poblando el formulario de solicitud de documentos
     * desde un webhook `order-paid` firmado, o desde el panel interno de
     * staff ya autenticado) — no para flujos de cara al cliente.
     *
     * SEGURIDAD: NO exponer directa ni indirectamente a un endpoint donde
     * un visitante no autenticado pueda elegir el order_id libremente; el
     * bridge, al no recibir lookup.email/external_id, salta su propio
     * chequeo de propiedad (ver alsernetbridge/api.php, case 'order.detail').
     * El llamador es responsable de garantizar que el order_id viene de una
     * fuente confiable (webhook firmado, panel autenticado con permiso).
     */
    public function getOrderDetailUnscoped(int $orderId): ?array
    {
        return $this->callApi('order.detail', [
            'order_id' => $orderId,
        ]);
    }

    /**
     * Variante de getOrderDetailUnscoped() por reference en vez de id — para
     * llamadores que solo conocen la reference del pedido (ej. documentos
     * denormalizados que guardaron order_reference pero no order_id).
     * Mismo alcance de seguridad: sin ownership-email, ver el aviso en
     * getOrderDetailUnscoped().
     */
    public function getOrderDetailByReferenceUnscoped(string $reference): ?array
    {
        return $this->callApi('order.detail', [
            'reference' => $reference,
        ]);
    }

    /**
     * Variante de getOrderDetail() por reference en vez de id — para cuando
     * el cliente solo conoce la reference de PrestaShop (ej. "XKBKNABJK"),
     * no el id numérico. Misma verificación de propiedad por email/external_id
     * que getOrderDetail(): el bridge resuelve el id a partir de la reference
     * y comprueba que el pedido pertenezca al cliente identificado por el
     * lookup antes de devolverlo (ver alsernet_order_detail()); sin email ni
     * external_id, se rechaza aquí mismo sin llamar al bridge (fail closed).
     */
    public function getOrderDetailByReference(string $reference, ?string $customerEmail = null, ?int $externalId = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'order.detail', 0);
        if ($lookup === null) {
            return null;
        }

        return $this->callApi('order.detail', [
            'reference' => $reference,
            'lookup' => $lookup,
        ]);
    }

    /**
     * Búsqueda de pedidos por id/reference (coincidencia parcial) — para
     * autocompletados internos del panel (Select2).
     *
     * @return array<int, array{id: int, reference: string}>
     */
    public function searchOrders(string $query, int $limit = 50): array
    {
        $data = $this->callApi('order.search', [
            'query' => $query,
            'limit' => $limit,
        ]);

        return $data['orders'] ?? [];
    }

    /**
     * Lista paginada de pedidos del cliente vía la accion dedicada `customer.orders`.
     * Es la fuente fiable de pedidos: `customer.helpdesk_context` puede devolver el
     * array `orders` vacio para algunos clientes aunque `orders_count` sea > 0.
     * El cliente se resuelve por email o, si se da, por id_customer de PrestaShop.
     *
     * @return array<string, mixed>|null
     */
    public function getCustomerOrders(string $email, ?int $prestashopCustomerId = null, int $limit = 10, int $page = 1): ?array
    {
        $lookup = ['email' => $this->normalizeEmail($email)];

        if ($prestashopCustomerId !== null) {
            $lookup['external_id'] = $prestashopCustomerId;
        }

        return $this->callApi('customer.orders', [
            'lookup' => $lookup,
            'limit' => $limit,
            'page' => $page,
        ]);
    }

    public function startOrderReturn(int $orderId, array $items, ?string $customerEmail = null, ?int $externalId = null, ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'order.start_return', $orderId);
        if ($lookup === null) {
            return null;
        }

        return $this->callApi('order.start_return', [
            'order_id' => $orderId,
            'items' => $items,
            'lookup' => $lookup,
        ], $idempotencyKey);
    }

    /**
     * Cambia el estado de un pedido de PrestaShop (via OrderHistory en el bridge:
     * respeta stock/facturas y, solo si $notify, el correo del estado). $stateId
     * es el id_order_state real de PrestaShop.
     *
     * @return array{order_id:int,state_id:int,state_name:string,notified:bool,changed:bool}|null
     */
    public function changeOrderStatus(int $orderId, int $stateId, bool $notify = false, ?string $customerEmail = null, ?int $externalId = null, ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'order.change_status', $orderId);
        if ($lookup === null) {
            return null;
        }

        return $this->callApi('order.change_status', [
            'order_id' => $orderId,
            'state_id' => $stateId,
            'notify' => $notify,
            'lookup' => $lookup,
        ], $idempotencyKey);
    }

    /**
     * Metadatos de documentos del pedido (facturas / albaranes): número + fecha.
     *
     * @return array{invoices:array<int,array>,delivery_slips:array<int,array>}|null
     */
    public function getOrderDocuments(int $orderId, ?string $customerEmail = null, ?int $externalId = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'order.documents', $orderId);
        if ($lookup === null) {
            return null;
        }

        return $this->callApi('order.documents', [
            'order_id' => $orderId,
            'lookup' => $lookup,
        ]);
    }

    /**
     * Fail-closed: las acciones por-pedido/carrito NUNCA van al bridge sin
     * lookup.email y/o lookup.external_id. Sin ninguno de los dos, el bridge
     * resolvería el pedido/carrito solo por su id secuencial, sin verificar
     * que pertenezca al cliente (IDOR). La propiedad la aplica el bridge;
     * aquí garantizamos que siempre tenga con qué aplicarla. external_id
     * tiene prioridad en el bridge (ver alsernet_resolve_customer) — se
     * admite porque el email del contacto de Helpdesk puede no coincidir con
     * el de su cuenta de PrestaShop (ver ContactAggregatorService::prestashop()).
     */
    private function buildOwnershipLookup(?string $customerEmail, ?int $externalId, string $action, int $subjectId): ?array
    {
        $hasEmail = $customerEmail !== null && trim($customerEmail) !== '';

        if (! $hasEmail && $externalId === null) {
            Log::warning('PrestashopContextService: llamada por-pedido/carrito bloqueada sin email ni external_id de cliente', [
                'action' => $action,
                'id' => $subjectId,
            ]);

            return null;
        }

        $lookup = [];
        if ($hasEmail) {
            $lookup['email'] = $this->normalizeEmail($customerEmail);
        }
        if ($externalId !== null) {
            $lookup['external_id'] = $externalId;
        }

        return $lookup;
    }

    /**
     * Cambia la dirección (envío/facturación) del pedido a una dirección
     * existente del mismo cliente.
     *
     * @return array{order_id:int,address_id:int,type:string}|null
     */
    public function setOrderAddress(int $orderId, int $addressId, string $type = 'delivery', ?string $customerEmail = null, ?int $externalId = null, ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'order.set_address', $orderId);
        if ($lookup === null) {
            return null;
        }

        return $this->callApi('order.set_address', [
            'order_id' => $orderId,
            'address_id' => $addressId,
            'type' => $type,
            'lookup' => $lookup,
        ], $idempotencyKey);
    }

    /**
     * Reenvía un correo estándar del pedido al cliente (whitelist de tipos).
     *
     * @return array{order_id:int,type:string,sent:bool,to:string}|null
     */
    public function sendOrderEmail(int $orderId, string $type, ?string $customerEmail = null, ?int $externalId = null, ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'order.send_email', $orderId);
        if ($lookup === null) {
            return null;
        }

        return $this->callApi('order.send_email', [
            'order_id' => $orderId,
            'type' => $type,
            'lookup' => $lookup,
        ], $idempotencyKey);
    }

    /**
     * Añade una nota interna a un pedido de PrestaShop (visible en el back
     * office). Usa la acción order.add_note del bridge; verifica propiedad por
     * el email del cliente igual que el resto de acciones de pedido.
     *
     * @return array{note_id:int|null,order_id:int,created_at:string,content:string}|null
     */
    public function addOrderNote(int $orderId, string $note, string $agentName, ?string $customerEmail = null, ?int $externalId = null, ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'order.add_note', $orderId);
        if ($lookup === null) {
            return null;
        }

        return $this->callApi('order.add_note', [
            'order_id' => $orderId,
            'note' => $note,
            'agent_name' => $agentName,
            'lookup' => $lookup,
        ], $idempotencyKey);
    }

    /**
     * Marca un pedido como candidato a envío al ERP (Gestión) — sin
     * ownership-email deliberadamente: es una acción interna del panel de
     * staff (Document), no de cara al cliente, sobre un order_id ya
     * confiable. Requiere idempotencyKey por ser acción de escritura.
     */
    public function flagOrderForErpSend(int $orderId, ?string $idempotencyKey = null): ?array
    {
        return $this->callApi('order.flag_for_erp_send', [
            'order_id' => $orderId,
        ], $idempotencyKey);
    }

    /**
     * Corrige nombre/email de un cliente ANÓNIMO de PrestaShop (email
     * placeholder anon_*) con datos reales hallados en las direcciones de
     * sus pedidos — usado por el comando de validación de documentos
     * pagados. Sin ownership-email: el propio email del cliente es el dato
     * roto que se está corrigiendo, no algo para verificar contra sí mismo.
     * El bridge re-verifica server-side que el email actual es anon_* antes
     * de escribir (ver alsernet_customer_fix_anonymous_profile).
     */
    public function fixAnonymousCustomerProfile(int $customerId, string $firstname, string $lastname, string $email, ?string $idempotencyKey = null): ?array
    {
        return $this->callApi('customer.fix_anonymous_profile', [
            'customer_id' => $customerId,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
        ], $idempotencyKey);
    }

    /**
     * Catálogo de estados de pedido de PrestaShop (id, name, color + flags) para
     * el desplegable de "Cambiar estado" del workspace. Cacheado 1h (cambian raras
     * veces). El id es el `id_order_state` real que espera changeOrderStatus().
     *
     * @return array<int, array{id:int,name:string,color:string,paid:int,shipped:int,delivery:int}>
     */
    public function getOrderStates(): array
    {
        $key = 'ps_order_states';
        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached;
        }

        try {
            $result = $this->callApi('order.states', []);
        } catch (\Throwable) {
            return [];
        }

        $states = $result['states'] ?? [];

        // No cachear una lista vacía: un fallo transitorio del bridge (ok=false,
        // o `states` ausente) dejaría el desplegable de "Cambiar estado" vacío
        // durante 1h para todos los agentes en vez de reintentar en la próxima
        // petición.
        if ($states !== []) {
            Cache::put($key, $states, 3600);
        }

        return $states;
    }

    /**
     * Asigna número de seguimiento (y opcionalmente transportista) a un pedido.
     * No envía correo por sí mismo — el aviso de envío se dispara al pasar el
     * pedido a "Enviado" con changeOrderStatus($notify: true).
     *
     * @return array{order_id:int,tracking_number:string,carrier_id:int}|null
     */
    public function setOrderTracking(int $orderId, string $trackingNumber, ?int $carrierId = null, ?string $customerEmail = null, ?int $externalId = null, ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'order.set_tracking', $orderId);
        if ($lookup === null) {
            return null;
        }

        $payload = [
            'order_id' => $orderId,
            'tracking_number' => $trackingNumber,
            'lookup' => $lookup,
        ];

        if ($carrierId !== null) {
            $payload['carrier_id'] = $carrierId;
        }

        return $this->callApi('order.set_tracking', $payload, $idempotencyKey);
    }

    /**
     * Cambia la dirección de envío/facturación del carrito real del cliente en
     * PrestaShop. El bridge exige que la dirección ya pertenezca al mismo
     * cliente — no admite direcciones arbitrarias.
     *
     * @return array{cart_id:int,address_id:int,type:string}|null
     */
    public function setCartAddress(int $cartId, int $addressId, string $type = 'delivery', ?string $customerEmail = null, ?int $externalId = null, ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'cart.set_address', $cartId);
        if ($lookup === null) {
            return null;
        }

        return $this->callApi('cart.set_address', [
            'cart_id' => $cartId,
            'address_id' => $addressId,
            'type' => $type,
            'lookup' => $lookup,
        ], $idempotencyKey);
    }

    /**
     * Añade un producto (opcionalmente una combinación) al carrito real del
     * cliente, o incrementa su cantidad si ya estaba.
     *
     * @return array{cart_id:int,product_id:int,attribute_id:int|null,quantity:int}|null
     */
    public function addCartProduct(int $cartId, int $productId, int $quantity = 1, ?int $attributeId = null, ?string $customerEmail = null, ?int $externalId = null, ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'cart.add_product', $cartId);
        if ($lookup === null) {
            return null;
        }

        $payload = [
            'cart_id' => $cartId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'lookup' => $lookup,
        ];

        if ($attributeId !== null) {
            $payload['attribute_id'] = $attributeId;
        }

        return $this->callApi('cart.add_product', $payload, $idempotencyKey);
    }

    /**
     * Cesta de INVITADO (sin cliente): el bridge solo la edita con el token
     * que emitió para ese id_cart + id_guest (widgetcontext), que el widget
     * entrega en el latido. Mismas acciones que las del cliente.
     *
     * @param  'add'|'update'|'remove'  $op
     * @return array<string, mixed>|null
     */
    public function guestCartOperation(string $op, int $cartId, string $guestToken, int $productId, int $quantity = 1, ?int $attributeId = null, ?string $idempotencyKey = null): ?array
    {
        $action = match ($op) {
            'add' => 'cart.add_product',
            'update' => 'cart.update_quantity',
            'remove' => 'cart.remove_product',
            default => null,
        };
        if ($action === null || $cartId <= 0 || $productId <= 0 || trim($guestToken) === '') {
            return null;
        }

        $payload = [
            'cart_id' => $cartId,
            'product_id' => $productId,
            'guest_cart_token' => $guestToken,
        ];
        if ($op !== 'remove') {
            $payload['quantity'] = $quantity;
        }
        if ($attributeId !== null) {
            $payload['attribute_id'] = $attributeId;
        }

        return $this->callApi($action, $payload, $idempotencyKey);
    }

    /**
     * Quita un producto (toda su cantidad) del carrito real del cliente.
     *
     * @return array{cart_id:int,product_id:int,attribute_id:int|null}|null
     */
    public function removeCartProduct(int $cartId, int $productId, ?int $attributeId = null, ?string $customerEmail = null, ?int $externalId = null, ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'cart.remove_product', $cartId);
        if ($lookup === null) {
            return null;
        }

        $payload = [
            'cart_id' => $cartId,
            'product_id' => $productId,
            'lookup' => $lookup,
        ];

        if ($attributeId !== null) {
            $payload['attribute_id'] = $attributeId;
        }

        return $this->callApi('cart.remove_product', $payload, $idempotencyKey);
    }

    /**
     * Fija la cantidad exacta de un producto en el carrito real del cliente
     * (0 lo elimina). El bridge calcula el delta y lo aplica vía
     * Cart::updateQty() de PrestaShop, no escritura directa — mantiene
     * consistentes stock y reglas de precio.
     *
     * @return array{cart_id:int,product_id:int,quantity:int}|null
     */
    public function updateCartProductQuantity(int $cartId, int $productId, int $quantity, ?int $attributeId = null, ?string $customerEmail = null, ?int $externalId = null, ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'cart.update_quantity', $cartId);
        if ($lookup === null) {
            return null;
        }

        $payload = [
            'cart_id' => $cartId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'lookup' => $lookup,
        ];

        if ($attributeId !== null) {
            $payload['attribute_id'] = $attributeId;
        }

        return $this->callApi('cart.update_quantity', $payload, $idempotencyKey);
    }

    /**
     * Aplica un cupón al carrito real del cliente. El bridge valida que el
     * cupón exista Y que aplique a ese carrito concreto (fechas, importe
     * mínimo, límite por cliente) antes de escribirlo.
     *
     * @return array{applied:bool,cart_id?:int,code?:string,error?:string}|null
     */
    public function applyCartVoucher(int $cartId, string $code, ?string $customerEmail = null, ?int $externalId = null, ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'cart.apply_voucher', $cartId);
        if ($lookup === null) {
            return null;
        }

        return $this->callApi('cart.apply_voucher', [
            'cart_id' => $cartId,
            'code' => $code,
            'lookup' => $lookup,
        ], $idempotencyKey);
    }

    /**
     * Quita un cupón (por código) del carrito real del cliente. El bridge solo
     * desengancha reglas que están realmente aplicadas a ESE carrito.
     *
     * @return array{removed:bool,cart_id?:int,code?:string,error?:string}|null
     */
    public function removeCartVoucher(int $cartId, string $code, ?string $customerEmail = null, ?int $externalId = null, ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'cart.remove_voucher', $cartId);
        if ($lookup === null) {
            return null;
        }

        return $this->callApi('cart.remove_voucher', [
            'cart_id' => $cartId,
            'code' => $code,
            'lookup' => $lookup,
        ], $idempotencyKey);
    }

    public function testConnection(): array
    {
        $start = microtime(true);

        try {
            $result = $this->callApi('customer.helpdesk_context', ['lookup' => ['email' => '__healthcheck__@invalid.local']]);
            $latencyMs = (int) ((microtime(true) - $start) * 1000);

            return [
                'ok' => $result !== null,
                'latency_ms' => $latencyMs,
                'error' => $result === null ? 'API no respondió correctamente.' : null,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'latency_ms' => (int) ((microtime(true) - $start) * 1000),
                'error' => $e->getMessage(),
            ];
        }
    }

    private function fetchContext(string $email, ?int $externalId = null, bool &$upstreamFailed = false): ?array
    {
        // El `helpdesk_context` falla (HTTP 500) para ciertos clientes del bridge.
        // No dejamos que eso aborte el contexto: capturamos y seguimos con el
        // fallback fiable basado en `customer.orders`.
        $lookup = ['email' => $email];
        if ($externalId !== null) {
            $lookup['external_id'] = $externalId;
        }

        try {
            $context = $this->callApi('customer.helpdesk_context', ['lookup' => $lookup]);
        } catch (PsUpstreamException) {
            $context = null;
            $upstreamFailed = true;
        }

        $found = is_array($context) && ($context['customer']['found'] ?? false);

        // Fallback robusto: el `helpdesk_context` del bridge falla (HTTP 500) o
        // devuelve `found=false` para ciertos clientes. La accion `customer.orders`
        // es fiable, asi que construimos/completamos el contexto con ella para que
        // los pedidos aparezcan siempre en la conversacion.
        if (! $found) {
            $list = $this->fetchOrdersList($email, $externalId);

            if (empty($list)) {
                return $context;
            }

            return [
                'customer' => array_filter([
                    'found' => true,
                    'id' => $externalId,
                    'email' => $email,
                    'orders_count' => count($list),
                ], fn ($v) => $v !== null),
                'orders' => array_map(fn ($o) => $this->mapOrder($o), $list),
                'carts' => [],
            ];
        }

        // `helpdesk_context` encontro al cliente pero devolvio `orders` vacio
        // aunque tenga pedidos: completamos con `customer.orders`.
        if (empty($context['orders']) && (int) ($context['customer']['orders_count'] ?? 0) > 0) {
            $list = $this->fetchOrdersList($email, $externalId);

            if (! empty($list)) {
                $context['orders'] = array_map(fn ($o) => $this->mapOrder($o), $list);
            }
        }

        return $context;
    }

    /**
     * Lista cruda de pedidos del cliente vía `customer.orders` (tolerante a fallos).
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchOrdersList(string $email, ?int $externalId = null): array
    {
        try {
            $orders = $this->getCustomerOrders($email, $externalId, 10, 1);
        } catch (\Throwable) {
            return [];
        }

        return $orders['orders'] ?? ($orders['data'] ?? []);
    }

    /**
     * Normaliza un pedido de `customer.orders` a la forma que consume el panel
     * de contexto (compatible con la salida de `helpdesk_context`).
     *
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>
     */
    private function mapOrder(array $o): array
    {
        return [
            'id' => $o['id'] ?? null,
            'reference' => $o['reference'] ?? null,
            'placed_at' => $o['created_at'] ?? ($o['placed_at'] ?? null),
            'payment_method' => $o['payment'] ?? null,
            'currency_sign' => $o['currency'] ?? '€',
            'state' => [
                'name' => $o['state_name'] ?? null,
                'color' => $o['state_color'] ?? null,
            ],
            'totals' => [
                'total' => $o['total'] ?? null,
                'products' => $o['subtotal'] ?? null,
                'shipping' => $o['shipping'] ?? null,
                'discount' => $o['discount'] ?? null,
            ],
            'lines' => [],
        ];
    }

    /**
     * Dispara revalidación en background si el entry está cerca de expirar.
     */
    private function maybeRevalidate(string $email, array $cached): void
    {
        $age = time() - ($cached['_cached_at'] ?? 0);
        $effectiveTtl = $cached['_ttl'] ?? (int) config('helpdeskprestashop.cache_ttl', 300);
        $staleGrace = (int) config('helpdeskprestashop.stale_grace', 30);

        if ($age >= ($effectiveTtl - $staleGrace)) {
            RefreshPsContextJob::dispatch($email)->afterCommit();
        }
    }

    private function putInCache(string $key, array $result): void
    {
        $ttl = $this->ttlFor($result);

        if ($ttl <= 0) {
            return;
        }

        $payload = $result;
        $payload['_cached_at'] = time();
        $payload['_ttl'] = $ttl;

        Cache::put($key, $payload, $ttl);
    }

    private function ttlFor(array $result): int
    {
        if (! ($result['customer']['found'] ?? false)) {
            return (int) config('helpdeskprestashop.miss_ttl', 60);
        }

        return (int) config('helpdeskprestashop.cache_ttl', 300);
    }

    private function cacheKey(string $email): string
    {
        return 'prestashop_ctx_'.md5($email);
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Central HTTP caller: builds the HMAC signature (with timestamp) and dispatches the request.
     * Write actions accept an optional idempotency key; one is auto-generated when not provided.
     *
     * Returns the decoded JSON body on success.
     * Returns null when the API responds with ok=false (semantic error: not-found, validation, etc.).
     *
     * @throws PsUpstreamException when the request cannot reach PrestaShop due to network errors,
     *                             HTTP 5xx responses, or an open circuit breaker.
     */
    private function callApi(string $action, array $payload, ?string $idempotencyKey = null): ?array
    {
        $apiUrl = (string) config('helpdeskprestashop.api_url', '');
        $secret = (string) config('helpdeskprestashop.webhook_secret', '');

        if ($apiUrl === '' || $secret === '') {
            Log::info('HelpdeskPrestashop: API URL o secreto no configurados — se devuelve null.', [
                'action' => $action,
            ]);

            return null;
        }

        if ($this->isCircuitOpen()) {
            Log::warning('HelpdeskPrestashop: circuit breaker abierto — request omitido.', [
                'action' => $action,
            ]);

            throw new PsUpstreamException('Servicio PrestaShop temporalmente no disponible (circuit breaker).');
        }

        $bodyJson = json_encode(array_merge(['action' => $action], $payload));
        $timestamp = time();
        $signature = HmacSigner::sign($secret, $timestamp, $bodyJson);

        $writeActions = [
            'customer.add_message',
            'order.add_note',
            'order.start_return',
            'order.flag_for_erp_send',
            'customer.fix_anonymous_profile',
            'order.change_status',
            'order.set_tracking',
            'order.set_address',
            'order.send_email',
            'cart.set_address',
            'cart.add_product',
            'cart.remove_product',
            'cart.update_quantity',
            'cart.apply_voucher',
            'cart.remove_voucher',
            'customer.address.create',
            'customer.address.update',
            'customer.create_voucher',
        ];
        // Acciones de escritura de extensiones (config/ext/*.php → 'ext_write_actions').
        $writeActions = array_merge($writeActions, (array) config('helpdeskprestashop.ext_write_actions', []));
        $headers = [
            'X-Alsernet-Signature' => $signature,
            'X-Alsernet-Timestamp' => (string) $timestamp,
            'X-Alsernet-Action' => $action,
            'Content-Type' => 'application/json',
        ];

        if (in_array($action, $writeActions, true)) {
            $headers['X-Alsernet-Idempotency-Key'] = $idempotencyKey ?? (string) Str::uuid();
        }

        $requestId = request()?->header('X-Request-Id');
        if ($requestId) {
            $headers['X-Request-Id'] = $requestId;
        }

        try {
            $response = Http::connectTimeout((int) config('helpdeskprestashop.http_connect_timeout', 2))
                ->timeout((int) config('helpdeskprestashop.http_timeout', 10))
                ->withHeaders($headers)
                ->withBody($bodyJson, 'application/json')
                ->post($apiUrl);

            // 404/422 con cuerpo {ok:false} = respuesta de negocio del bridge
            // (pedido/RMA de otro cliente, recurso inexistente, validación):
            // el puente está vivo, así que no es un fallo de infraestructura ni
            // debe sumar al circuit breaker. Antes se convertía en
            // PsUpstreamException y el panel decía "PrestaShop no responde".
            if (in_array($response->status(), [404, 422], true) && is_array($response->json()) && ($response->json('ok') === false)) {
                Log::info('HelpdeskPrestashop: el bridge rechazó la acción.', [
                    'action' => $action,
                    'status' => $response->status(),
                    'error' => $response->json('error'),
                ]);

                return null;
            }

            if (! $response->successful()) {
                Log::warning('HelpdeskPrestashop: respuesta no exitosa del upstream.', [
                    'action' => $action,
                    'status' => $response->status(),
                ]);
                $this->recordFailure();

                throw new PsUpstreamException(
                    "El servidor PrestaShop respondió con HTTP {$response->status()}."
                );
            }

            $decoded = $response->json();

            if (! ($decoded['ok'] ?? false)) {
                Log::warning('HelpdeskPrestashop: API devolvió ok=false.', [
                    'action' => $action,
                    'error' => $decoded['error'] ?? 'unknown',
                ]);
                // ok=false con HTTP 200 = error semántico (no-found, validation, etc.),
                // no es un fallo de red. No abre el circuit breaker.

                return null;
            }

            $this->recordSuccess();

            return $decoded['data'] ?? null;
        } catch (PsUpstreamException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('HelpdeskPrestashop: error de red al llamar al API.', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            $this->recordFailure();

            throw new PsUpstreamException(
                'Error de red al contactar PrestaShop: '.$e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Busca clientes de PrestaShop por email/id/nombre/NIF — usado por
     * HelpdeskIntegration (verificación de identidad, "Vincular plataforma")
     * y por CustomerCommerceSyncService (autoenlace al abrir conversación).
     * Antes esta búsqueda se hacía con una conexión directa a la BD de
     * PrestaShop desde Laravel (DB::connection('prestashop')); ahora pasa
     * por el bridge, igual que el resto de la integración — sin exponer
     * credenciales de esa BD en el .env de Laravel, y con caché corta para
     * no repetir la query en cada tecla del buscador.
     *
     * $type: 'email'|'id'|'name'|'nif'|'name_or_nif' — la inferencia de
     * 'auto' sigue en el caller (CustomerCommerceSyncService), no aquí.
     *
     * A diferencia de la mayoría de lecturas de este servicio, esta NO
     * atrapa PsUpstreamException — CustomerCommerceSyncService::
     * searchCustomersOrFail() necesita distinguir "sin resultados" de
     * "la plataforma no respondió" (se lo pasa al modal de búsqueda como
     * platform_error). Quien quiera la versión que se degrada a [] ya tiene
     * ese wrapper en CustomerCommerceSyncService::searchCustomers().
     *
     * @return array<int, array{id:string,name:string,email:string,meta:string,nif:?string,phone:?string,city:?string,active:bool,created_at:string,gestion_id:?int}>
     *
     * @throws PsUpstreamException
     */
    public function searchCustomers(string $query, string $type = 'email', int $limit = 10): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return [];
        }

        $cacheKey = 'ps.customer_search.'.md5($query.'|'.$type.'|'.$limit);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $data = $this->callApi('customer.search', [
            'query' => $query,
            'type' => $type,
            'limit' => $limit,
        ]);

        $customers = $data['customers'] ?? [];

        Cache::put($cacheKey, $customers, 30);

        return $customers;
    }

    /**
     * Returns PS shipping addresses for a customer by email.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCustomerAddresses(string $email, ?int $externalId = null): array
    {
        if ($email === '' && $externalId === null) {
            return [];
        }

        $lookup = [];
        if ($email !== '') {
            $lookup['email'] = $this->normalizeEmail($email);
        }
        if ($externalId !== null) {
            $lookup['external_id'] = $externalId;
        }

        try {
            $data = $this->callApi('customer.addresses', ['lookup' => $lookup]);
        } catch (PsUpstreamException) {
            return [];
        }

        return $data['addresses'] ?? [];
    }

    /**
     * Crea una dirección nueva para el cliente. $data admite: alias,
     * firstname, lastname, company, address1, address2, postcode, city,
     * id_country, id_state, phone, phone_mobile.
     *
     * @return array{id:int}|null
     */
    public function createCustomerAddress(array $data, ?string $customerEmail = null, ?int $externalId = null, ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'customer.address.create', 0);
        if ($lookup === null) {
            return null;
        }

        return $this->callApi('customer.address.create', $data + ['lookup' => $lookup], $idempotencyKey);
    }

    /**
     * Llamada genérica al bridge para las extensiones del módulo (servicios en
     * app/Services/Ext). Misma firma HMAC, circuit breaker e idempotencia que
     * las acciones nativas. Las de escritura deben declararse en
     * config('helpdeskprestashop.ext_write_actions') (ver config/ext/*.php).
     *
     * @throws PsUpstreamException
     */
    public function callBridge(string $action, array $payload, ?string $idempotencyKey = null): ?array
    {
        return $this->callApi($action, $payload, $idempotencyKey);
    }

    /**
     * Puerta de entrada del catálogo de acciones de la IA (HelpdeskAiPrompts):
     * solo deja pasar las acciones de config('ai-actions.bridge_allowlist').
     * Cualquier otra se rechaza SIN llamar al bridge. Las claves de la lista
     * contienen puntos, por eso se lee el array completo y no con notación de
     * puntos.
     *
     * @throws \InvalidArgumentException si la acción no está en la lista blanca
     * @throws PsUpstreamException
     */
    public function callAllowedAction(string $action, array $payload, ?string $idempotencyKey = null): ?array
    {
        $allowlist = (array) config('ai-actions.bridge_allowlist', []);

        if (! isset($allowlist[$action])) {
            Log::warning('PrestashopContextService: acción del bridge fuera de la lista blanca de la IA — rechazada.', [
                'action' => $action,
            ]);

            throw new \InvalidArgumentException("La acción «{$action}» no está permitida para el asistente IA.");
        }

        $isWrite = ($allowlist[$action]['mode'] ?? 'read') === 'write';

        return $this->callApi($action, $payload, $isWrite ? $idempotencyKey : null);
    }

    /**
     * Lookup de propiedad (email y/o external_id) para acciones por cliente;
     * null si no hay forma de identificar al cliente (la llamada no debe hacerse).
     *
     * @return array{email?: string, external_id?: int}|null
     */
    public function ownershipLookup(?string $customerEmail, ?int $externalId, string $action): ?array
    {
        return $this->buildOwnershipLookup($customerEmail, $externalId, $action, 0);
    }

    /**
     * Crea un vale de compensación (importe fijo, un solo uso) a nombre del
     * cliente. El código lo genera el bridge (GES-XXXX); aquí solo viajan
     * importe en céntimos, validez y motivo. El límite por rol lo aplica el
     * controlador; el bridge tiene además su propio tope duro (500 €).
     *
     * @return array{created:bool, id?:int, code?:string, amount?:float, date_to?:string, error?:string}|null
     */
    public function createCompensationVoucher(int $amountCents, int $validityDays, string $reason, string $agentName, ?string $customerEmail = null, ?int $externalId = null, ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'customer.create_voucher', 0);
        if ($lookup === null) {
            return null;
        }

        $result = $this->callApi('customer.create_voucher', [
            'lookup' => $lookup,
            'amount_cents' => $amountCents,
            'validity_days' => $validityDays,
            'reason' => $reason,
            'agent' => $agentName,
        ], $idempotencyKey);

        if ($customerEmail) {
            $this->forgetCache($customerEmail);
        }

        return $result;
    }

    /**
     * Actualiza una dirección existente (parcial — solo los campos
     * presentes en $data). El bridge verifica que pertenezca al cliente
     * resuelto por email/external_id antes de escribir.
     *
     * @return array{id:int}|null
     */
    public function updateCustomerAddress(int $addressId, array $data, ?string $customerEmail = null, ?int $externalId = null, ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->buildOwnershipLookup($customerEmail, $externalId, 'customer.address.update', $addressId);
        if ($lookup === null) {
            return null;
        }

        return $this->callApi('customer.address.update', $data + [
            'address_id' => $addressId,
            'lookup' => $lookup,
        ], $idempotencyKey);
    }

    /**
     * Provincias/estados de un país, para el desplegable del formulario de
     * dirección. Cacheado 1h (cambian prácticamente nunca).
     *
     * @return array<int, array{id:int, name:string}>
     */
    public function getCountryStates(int $countryId): array
    {
        $cacheKey = 'ps.country_states.'.$countryId;
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        try {
            $data = $this->callApi('country.states', ['id_country' => $countryId]);
        } catch (PsUpstreamException) {
            return [];
        }

        $states = $data['states'] ?? [];

        if ($states !== []) {
            Cache::put($cacheKey, $states, 3600);
        }

        return $states;
    }

    /**
     * Returns the customer's return (RMA) history — status, reason, line items.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCustomerReturns(string $email, ?int $externalId = null): array
    {
        if ($email === '' && $externalId === null) {
            return [];
        }

        $lookup = [];
        if ($email !== '') {
            $lookup['email'] = $this->normalizeEmail($email);
        }
        if ($externalId !== null) {
            $lookup['external_id'] = $externalId;
        }

        try {
            $data = $this->callApi('customer.returns', ['lookup' => $lookup]);
        } catch (PsUpstreamException) {
            return [];
        }

        return $data['returns'] ?? [];
    }

    /**
     * Returns the customer's own vouchers/cart rules (code, discount, validity).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCustomerVouchers(string $email, ?int $externalId = null): array
    {
        if ($email === '' && $externalId === null) {
            return [];
        }

        $lookup = [];
        if ($email !== '') {
            $lookup['email'] = $this->normalizeEmail($email);
        }
        if ($externalId !== null) {
            $lookup['external_id'] = $externalId;
        }

        try {
            $data = $this->callApi('customer.vouchers', ['lookup' => $lookup]);
        } catch (PsUpstreamException) {
            return [];
        }

        return $data['vouchers'] ?? [];
    }

    /**
     * Returns the customer's native PrestaShop message threads (contact/service).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCustomerMessages(string $email, ?int $externalId = null): array
    {
        if ($email === '' && $externalId === null) {
            return [];
        }

        $lookup = [];
        if ($email !== '') {
            $lookup['email'] = $this->normalizeEmail($email);
        }
        if ($externalId !== null) {
            $lookup['external_id'] = $externalId;
        }

        try {
            $data = $this->callApi('customer.messages', ['lookup' => $lookup]);
        } catch (PsUpstreamException) {
            return [];
        }

        return $data['messages'] ?? [];
    }

    /**
     * Returns the customer's wishlist products (leofeature_wishlist module).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCustomerWishlist(string $email, ?int $externalId = null): array
    {
        if ($email === '' && $externalId === null) {
            return [];
        }

        $lookup = [];
        if ($email !== '') {
            $lookup['email'] = $this->normalizeEmail($email);
        }
        if ($externalId !== null) {
            $lookup['external_id'] = $externalId;
        }

        try {
            $data = $this->callApi('customer.wishlist', ['lookup' => $lookup]);
        } catch (PsUpstreamException) {
            return [];
        }

        return $data['items'] ?? [];
    }

    /**
     * Returns the customer's real refunds (order_slip) — distinct from
     * getCustomerReturns(), which is the RMA request, not the money back.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCustomerRefunds(string $email, ?int $externalId = null): array
    {
        if ($email === '' && $externalId === null) {
            return [];
        }

        $lookup = [];
        if ($email !== '') {
            $lookup['email'] = $this->normalizeEmail($email);
        }
        if ($externalId !== null) {
            $lookup['external_id'] = $externalId;
        }

        try {
            $data = $this->callApi('customer.refunds', ['lookup' => $lookup]);
        } catch (PsUpstreamException) {
            return [];
        }

        return $data['refunds'] ?? [];
    }

    /**
     * Returns available PS categories for the search filter dropdown.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCategories(?string $lang = null): array
    {
        // Version-scoped key: a catalog change (price drop / back in stock) bumps
        // the version via forgetCatalogCache(), orphaning every lang variant at
        // once instead of trying to enumerate and forget each language key.
        $cacheKey = 'ps.categories.v'.$this->catalogCacheVersion().'.'.($lang ?? 'default');

        return Cache::remember($cacheKey, 3600, function () use ($lang): array {
            $payload = [];

            if ($lang !== null) {
                $payload['lang'] = $lang;
            }

            try {
                $data = $this->callApi('product.categories', $payload);
            } catch (PsUpstreamException) {
                return [];
            }

            return $data['categories'] ?? [];
        });
    }

    private function catalogCacheVersion(): int
    {
        return (int) Cache::rememberForever('ps.catalog.version', fn (): int => 1);
    }

    /**
     * Invalidate all cached catalog data (categories in every language) by
     * bumping the catalog cache version. Called from a listener on PrestaShop
     * price-drop / back-in-stock events.
     */
    public function forgetCatalogCache(): void
    {
        Cache::forever('ps.catalog.version', $this->catalogCacheVersion() + 1);
    }

    /**
     * Busca productos en PrestaShop via el bridge por texto, con filtros
     * opcionales (marca, categoría, precio, stock, orden — ver
     * alsernet_product_search() en el bridge). Devuelve array de productos
     * normalizados (vacío si el bridge no los soporta o falla).
     *
     * @param  array{brand?:string,category?:string,price_min?:float,price_max?:float,in_stock?:bool,sort?:string}  $filters
     * @return array<int, array<string, mixed>>
     */
    public function searchProducts(string $query, int $limit = 10, ?string $lang = null, int $offset = 0, bool $inStockOnly = false, array $filters = []): array
    {
        return $this->searchProductsWithMeta($query, $limit, $filters, $lang, $offset, $inStockOnly)['products'];
    }

    /**
     * Como searchProducts(), pero además indica qué motor de búsqueda del
     * bridge respondió y qué filtros hubo que relajar para no devolver 0
     * resultados. La usa BridgeCatalogDriver::searchWithFilters() (la
     * herramienta de búsqueda de producto del bot).
     *
     * @param  array{brand?:string,category?:string,price_min?:float,price_max?:float,in_stock?:bool,sort?:string}  $filters
     * @return array{products: array<int, array<string, mixed>>, relaxed: array<int, string>, engine: ?string}
     */
    public function searchProductsWithMeta(string $query, int $limit = 10, array $filters = [], ?string $lang = null, int $offset = 0, bool $inStockOnly = false): array
    {
        $empty = ['products' => [], 'relaxed' => [], 'engine' => null];

        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return $empty;
        }

        // TTL corto (no versionado con el catálogo): mitiga tecleo rápido/doble
        // envío sobre el mismo texto sin arriesgar resultados obsoletos de stock.
        $cacheKey = 'ps.search.meta.'.md5($query.'|'.$limit.'|'.($lang ?? 'default').'|'.$offset.'|'.($inStockOnly ? 1 : 0).'|'.json_encode($filters));

        return Cache::remember($cacheKey, 45, function () use ($query, $limit, $lang, $offset, $inStockOnly, $filters, $empty): array {
            $payload = array_merge($filters, ['query' => $query, 'limit' => $limit, 'offset' => $offset]);

            if ($lang !== null) {
                $payload['lang'] = $lang;
            }

            if ($inStockOnly) {
                $payload['in_stock'] = true;
            }

            try {
                $result = $this->callApi('product.search', $payload);
            } catch (PsUpstreamException) {
                return $empty;
            }

            if (! is_array($result)) {
                return $empty;
            }

            $products = $result['products'] ?? $result;

            if (! is_array($products)) {
                return $empty;
            }

            return [
                'products' => array_map(fn ($p) => $this->normalizeProduct($p), $products),
                'relaxed' => is_array($result['relaxed'] ?? null) ? array_values($result['relaxed']) : [],
                'engine' => isset($result['engine']) ? (string) $result['engine'] : null,
            ];
        });
    }

    /**
     * Busca un producto específico en PrestaShop por su ID numérico.
     * Devuelve null si el bridge no lo encuentra o falla.
     *
     * @return array<string, mixed>|null
     */
    public function getProductById(int $id, ?string $lang = null): ?array
    {
        return $this->fetchProductByKey('product_id', $id, $lang);
    }

    /**
     * Busca un producto específico en PrestaShop por su referencia/SKU (ej. C112972).
     * Devuelve null si el bridge no lo encuentra o falla.
     *
     * @return array<string, mixed>|null
     */
    public function getProductByReference(string $reference, ?string $lang = null): ?array
    {
        return $this->fetchProductByKey('reference', $reference, $lang);
    }

    /**
     * Precio+stock de un producto/combinación/país concretos — usado por el
     * job de validación de precio de Erp (ValidatePriceFromGestion) para
     * comparar contra el precio de Gestión. Sin caché deliberadamente (la
     * validación de precio necesita el dato fresco, no una copia de hasta
     * varios minutos), y sin ownership-email (no es un dato de cliente).
     *
     * @return array{price_with_tax: float, stock: int}|null
     */
    public function getProductPriceDetail(int $productId, int $productAttributeId, int $countryId): ?array
    {
        return $this->callApi('product.price_detail', [
            'product_id' => $productId,
            'product_attribute_id' => $productAttributeId,
            'country_id' => $countryId,
        ]);
    }

    /**
     * Lista specific_price activos (o todos sin expirar) — usado por
     * Erp\Console\Commands\SyncSpecificPrices para descubrir qué productos
     * necesitan una validación de precio programada.
     *
     * @return array<int, array{id_specific_price:int, id_product:int, id_product_attribute:int, id_country:int, from:?string, to:?string, reference:?string}>
     */
    public function listSpecificPrices(string $scope = 'active', int $limit = 500): array
    {
        $data = $this->callApi('specific_price.list', [
            'scope' => $scope,
            'limit' => $limit,
        ]);

        return $data['items'] ?? [];
    }

    /**
     * Núcleo compartido para búsqueda exacta de producto por un campo concreto.
     *
     * @return array<string, mixed>|null
     */
    private function fetchProductByKey(string $key, mixed $value, ?string $lang): ?array
    {
        // Version-scoped como getCategories(): un cambio de catálogo
        // (forgetCatalogCache()) invalida también estas búsquedas puntuales.
        $cacheKey = 'ps.product.v'.$this->catalogCacheVersion().'.'.md5($key.'|'.$value.'|'.($lang ?? 'default'));

        return Cache::remember($cacheKey, 90, function () use ($key, $value, $lang): ?array {
            $payload = [$key => $value];

            if ($lang !== null) {
                $payload['lang'] = $lang;
            }

            try {
                $result = $this->callApi('product.get', $payload);
            } catch (PsUpstreamException) {
                return null;
            }

            if (! is_array($result)) {
                return null;
            }

            $product = $result['product'] ?? $result;

            if (! is_array($product) || empty($product)) {
                return null;
            }

            return $this->normalizeProduct($product);
        });
    }

    /**
     * Extrae productos únicos del historial de pedidos del cliente.
     * Útil para sugerir recompras.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProductsFromHistory(string $email, int $limit = 6): array
    {
        $context = $this->getCustomerContext($email);
        $orders = $context['orders'] ?? [];
        $seen = [];
        $products = [];

        foreach ($orders as $order) {
            $lines = $order['lines'] ?? $order['products'] ?? [];

            foreach ($lines as $line) {
                $key = (string) ($line['id_product'] ?? $line['product_id'] ?? $line['name'] ?? '');

                if ($key === '' || isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $products[] = [
                    'id' => $line['id_product'] ?? $line['product_id'] ?? null,
                    'name' => $line['name'] ?? $line['product_name'] ?? '',
                    'sku' => $line['reference'] ?? $line['sku'] ?? null,
                    'price' => (float) ($line['unit_price'] ?? $line['price'] ?? 0),
                    'image' => $line['image_url'] ?? $line['image'] ?? null,
                    'url' => $line['url'] ?? null,
                    'from_history' => true,
                ];

                if (count($products) >= $limit) {
                    break 2;
                }
            }
        }

        return $products;
    }

    /**
     * Normaliza un producto del bridge al formato esperado por el frontend.
     *
     * @param  array<string, mixed>  $p
     * @return array<string, mixed>
     */
    private function normalizeProduct(array $p): array
    {
        $price = (float) ($p['price'] ?? $p['unit_price'] ?? 0);
        $taxRate = (float) ($p['tax_rate'] ?? 0);

        return [
            'id' => $p['id'] ?? $p['id_product'] ?? null,
            'name' => $p['name'] ?? '',
            'sku' => $p['reference'] ?? $p['sku'] ?? null,
            'price' => $price,
            'tax_rate' => $taxRate,
            'price_with_tax' => isset($p['price_with_tax'])
                ? (float) $p['price_with_tax']
                : ($taxRate > 0 ? round($price * (1 + $taxRate / 100), 2) : $price),
            'price_original' => isset($p['price_original']) && $p['price_original'] !== null
                ? (float) $p['price_original']
                : null,
            'has_discount' => (bool) ($p['has_discount'] ?? false),
            'stock' => isset($p['stock']) ? (int) $p['stock'] : null,
            'in_stock' => isset($p['in_stock']) ? (bool) $p['in_stock'] : null,
            'brand' => $p['brand'] ?? null,
            'category' => $p['category'] ?? null,
            'description' => $p['description'] ?? null,
            'ean13' => $p['ean13'] ?? null,
            'image' => $p['image_url'] ?? $p['image'] ?? null,
            'url' => $p['url'] ?? null,
            // Combinaciones (live commerce): con combinaciones el chat abre la
            // ficha en vez de añadir directo.
            'id_product_attribute' => isset($p['id_product_attribute']) ? (int) $p['id_product_attribute'] : 0,
            'has_combinations' => (bool) ($p['has_combinations'] ?? false),
            'available_for_order' => (bool) ($p['available_for_order'] ?? true),
            // Precio final calculado por PrestaShop (getPriceStatic): el que ve
            // el visitante en la ficha, con todas las reglas de descuento.
            'final_price_with_tax' => isset($p['final_price_with_tax']) ? (float) $p['final_price_with_tax'] : null,
            'final_price_original' => isset($p['final_price_original']) ? (float) $p['final_price_original'] : null,
        ];
    }
}
