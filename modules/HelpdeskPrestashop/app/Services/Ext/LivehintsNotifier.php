<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Events\Ext\LivehintsHint;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Spatie\Activitylog\Models\Activity;

/**
 * Núcleo de la extensión "livehints": encuentra la conversación ABIERTA del
 * cliente afectado por un webhook de PrestaShop y le manda el aviso en vivo.
 *
 * Mismo criterio que BroadcastCartUpdated: cliente del helpdesk → su
 * conversación con estado is_open más reciente (last_message_at). Además de
 * por email (lo único que usa el carrito), aquí se resuelve por el
 * id_customer de PrestaShop vinculado en helpdesk_customer_external_ids,
 * porque los webhooks de pedidos (order.created, order.status_changed,
 * order.return_requested) no traen email.
 *
 * Nada de lo que hay aquí lanza: el receptor del webhook devolvería 500 y
 * PrestaShop reintentaría el evento entero (con los demás listeners).
 */
class LivehintsNotifier
{
    public const TYPES = [
        'back_in_stock',
        'price_dropped',
        'cart_abandoned',
        'order_created',
        'order_status',
        'order_returned',
    ];

    public function __construct(
        private readonly PrestashopContextService $prestashop
    ) {}

    public function enabled(string $type): bool
    {
        return (bool) config('helpdeskprestashop.ext.livehints.hints.'.$type, true);
    }

    /**
     * Cliente del helpdesk vinculado a este cliente de PrestaShop.
     * Primero el vínculo explícito por id_customer (identidad exacta en la
     * tienda); si no lo hay, el email, como BroadcastCartUpdated.
     */
    public function resolveCustomer(?string $email, ?int $psCustomerId): ?Customer
    {
        if ($psCustomerId !== null && $psCustomerId > 0) {
            $customer = Customer::findByExternalId('prestashop', (string) $psCustomerId);
            if ($customer !== null) {
                return $customer;
            }
        }

        $email = trim((string) $email);
        if ($email === '') {
            return null;
        }

        return Customer::query()->where('email', $email)->first();
    }

    public function openConversationId(Customer $customer): ?int
    {
        $id = Conversation::query()
            ->where('customer_id', $customer->id)
            ->whereHas('status', fn ($q) => $q->where('is_open', true))
            ->orderByDesc('last_message_at')
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Conversaciones abiertas cuyo cliente tiene este producto en su lista de
     * deseos o un aviso de reposición apuntado desde el panel.
     *
     * Indexado por id de conversación.
     *
     * @return array<int, array{customer: Customer, source: string, item: array<string, mixed>|null}>
     */
    public function interestedInProduct(int $productId): array
    {
        $max = max(1, (int) config('helpdeskprestashop.ext.livehints.max_open_conversations', 300));

        $rows = Conversation::query()
            ->whereNotNull('customer_id')
            ->whereHas('status', fn ($q) => $q->where('is_open', true))
            ->orderByDesc('last_message_at')
            ->limit($max)
            ->get(['id', 'customer_id']);

        // Una sola conversación por cliente: la abierta más reciente.
        $byCustomer = [];
        foreach ($rows as $row) {
            $byCustomer[(int) $row->customer_id] ??= (int) $row->id;
        }

        if ($byCustomer === []) {
            return [];
        }

        $customers = Customer::query()
            ->with('externalIds')
            ->whereIn('id', array_keys($byCustomer))
            ->get()
            ->keyBy('id');

        $alerts = $this->stockAlertCustomerIds($productId, array_keys($byCustomer));
        $lookups = 0;
        $out = [];

        foreach ($byCustomer as $customerId => $conversationId) {
            $customer = $customers->get($customerId);
            if ($customer === null) {
                continue;
            }

            $item = $this->wishlistItem($customer, $productId, $lookups);
            if ($item !== null) {
                $out[$conversationId] = ['customer' => $customer, 'source' => 'wishlist', 'item' => $item];

                continue;
            }

            if (isset($alerts[$customerId])) {
                $out[$conversationId] = ['customer' => $customer, 'source' => 'stock_alert', 'item' => null];
            }
        }

        return $out;
    }

    /**
     * Producto de la lista de deseos del cliente, o null. Lee primero el
     * contexto ya cacheado del panel (sin HTTP); si no está, pregunta al
     * puente — como mucho `wishlist_bridge_lookups` clientes por evento — y
     * guarda la lista unos minutos para los siguientes eventos de producto.
     *
     * @return array<string, mixed>|null
     */
    private function wishlistItem(Customer $customer, int $productId, int &$lookups): ?array
    {
        $items = $this->wishlistFor($customer, $lookups);

        foreach ($items as $item) {
            if ((int) ($item['id'] ?? $item['id_product'] ?? 0) === $productId) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function wishlistFor(Customer $customer, int &$lookups): array
    {
        $email = trim((string) $customer->email);
        $external = $customer->externalIdFor('prestashop');
        $externalId = is_numeric($external) ? (int) $external : null;

        if ($email === '' && $externalId === null) {
            return [];
        }

        if ($email !== '') {
            $ctx = $this->prestashop->peekCachedContext($email);
            if (is_array($ctx) && ($ctx['customer']['found'] ?? false) && is_array($ctx['wishlist'] ?? null)) {
                return $ctx['wishlist'];
            }
        }

        $cacheKey = 'ps.livehints.wishlist.'.$customer->id;
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        if ($lookups >= max(0, (int) config('helpdeskprestashop.ext.livehints.wishlist_bridge_lookups', 10))) {
            return [];
        }
        $lookups++;

        $items = array_values(array_map(fn (array $i): array => [
            'id' => (int) ($i['id'] ?? $i['id_product'] ?? 0),
            'name' => (string) ($i['name'] ?? ''),
            'price' => isset($i['price']) ? (float) $i['price'] : null,
            'price_with_tax' => isset($i['price_with_tax']) ? (float) $i['price_with_tax'] : null,
            'tax_rate' => isset($i['tax_rate']) ? (float) $i['tax_rate'] : null,
        ], array_filter($this->prestashop->getCustomerWishlist($email, $externalId), 'is_array')));

        Cache::put($cacheKey, $items, now()->addMinutes(max(1, (int) config('helpdeskprestashop.ext.livehints.wishlist_cache_minutes', 10))));

        return $items;
    }

    /**
     * Clientes con aviso de reposición de este producto. PrestaShop no expone
     * por el puente los avisos de ps_emailalerts de un cliente, así que solo
     * se conocen los que un agente apuntó desde el panel (acción
     * catalog.stock_alert, que deja la actividad 'ps.catalog.stock_alert').
     *
     * @param  array<int, int>  $customerIds
     * @return array<int, true>
     */
    private function stockAlertCustomerIds(int $productId, array $customerIds): array
    {
        $model = Activity::class;
        if ($customerIds === [] || ! class_exists($model)) {
            return [];
        }

        try {
            $rows = $model::query()
                ->where('log_name', 'helpdeskprestashop')
                ->where('description', 'ps.catalog.stock_alert')
                ->where('subject_type', (new Customer)->getMorphClass())
                ->whereIn('subject_id', $customerIds)
                ->where('created_at', '>=', now()->subDays(max(1, (int) config('helpdeskprestashop.ext.livehints.stock_alert_days', 180))))
                ->get(['subject_id', 'properties']);
        } catch (\Throwable $e) {
            Log::warning('livehints: no se pudieron leer los avisos de stock', ['error' => $e->getMessage()]);

            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $props = $row->properties;
            $pid = is_object($props) && method_exists($props, 'get') ? $props->get('product_id') : ($props['product_id'] ?? null);
            if ((int) $pid === $productId) {
                $out[(int) $row->subject_id] = true;
            }
        }

        return $out;
    }

    /**
     * Referencia del pedido (ABC123XYZ) a partir del contexto ya cacheado del
     * cliente — sin HTTP. Null si no está en caché.
     */
    public function cachedOrderReference(Customer $customer, int $orderId): ?string
    {
        $email = trim((string) $customer->email);
        if ($email === '' || $orderId <= 0) {
            return null;
        }

        $ctx = $this->prestashop->peekCachedContext($email);
        foreach ((array) ($ctx['orders'] ?? []) as $order) {
            if (is_array($order) && (int) ($order['id'] ?? $order['order_id'] ?? 0) === $orderId) {
                $ref = trim((string) ($order['reference'] ?? ''));

                return $ref !== '' ? $ref : null;
            }
        }

        return null;
    }

    /**
     * Nombre del estado de pedido de PrestaShop (catálogo cacheado 1 h).
     */
    public function orderStateName(?int $stateId): ?string
    {
        if (! $stateId) {
            return null;
        }

        try {
            foreach ($this->prestashop->getOrderStates() as $state) {
                if ((int) ($state['id'] ?? 0) === $stateId) {
                    $name = trim((string) ($state['name'] ?? ''));

                    return $name !== '' ? $name : null;
                }
            }
        } catch (\Throwable) {
            // Sin catálogo: el front dice "otro estado".
        }

        return null;
    }

    /**
     * Ficha del producto (nombre, precio con IVA) cuando no llega en el
     * payload ni en la lista de deseos. Cacheada 90 s por el servicio.
     *
     * @return array<string, mixed>|null
     */
    public function product(int $productId): ?array
    {
        try {
            return $this->prestashop->getProductById($productId);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Envía el aviso si el tipo está activo y no se mandó ya el mismo aviso a
     * esa conversación hace poco (reintentos del webhook, cron repetido).
     *
     * @param  array<string, mixed>  $data
     */
    public function send(int $conversationId, string $type, string $dedupeKey, array $data): bool
    {
        if (! $this->enabled($type)) {
            return false;
        }

        $minutes = (int) config('helpdeskprestashop.ext.livehints.dedupe_minutes', 30);
        if ($minutes > 0) {
            $key = 'ps.livehints.sent.'.md5($type.'|'.$conversationId.'|'.$dedupeKey);
            if (! Cache::add($key, 1, now()->addMinutes($minutes))) {
                return false;
            }
        }

        try {
            LivehintsHint::dispatch($conversationId, $type, $data);
        } catch (\Throwable $e) {
            Log::warning('livehints: no se pudo emitir el aviso en vivo', [
                'type' => $type,
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Ejecuta un listener sin dejar escapar excepciones.
     */
    public function guard(string $type, callable $fn): void
    {
        if (! $this->enabled($type)) {
            return;
        }

        try {
            $fn();
        } catch (\Throwable $e) {
            Log::warning('livehints: fallo preparando el aviso', ['type' => $type, 'error' => $e->getMessage()]);
        }
    }
}
