<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Modules\HelpdeskPrestashop\Services\PrestashopProductQueryService;

class ProductSearchController extends Controller
{
    public function __construct(
        private readonly PrestashopContextService $ps,
        private readonly PrestashopProductQueryService $psProducts,
    ) {}

    /**
     * Detalle completo de un producto PS: datos básicos (precios desde bridge) + atributos/combinaciones.
     */
    public function detail(Request $request, Customer $customer, int $productId): JsonResponse
    {
        $this->authorize('view', $customer);

        $lang = $customer->language ?: 'es';

        // Precios vienen del bridge (PS module endpoint); fallback a BD directa
        $product = $this->ps->getProductById($productId, $lang)
            ?? $this->psProducts->findById($productId, $lang);

        if ($product === null) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado.'], 404);
        }

        $attrs = $this->psProducts->getProductAttributes($productId, $lang);

        return response()->json([
            'success' => true,
            'product' => $product,
            'attributes' => $attrs['attributes'],
            'combinations' => $attrs['combinations'],
        ]);
    }

    /**
     * Alternativas del mismo fabricante o categoría para un producto PS.
     * Devuelve hasta 6 productos distintos al solicitado.
     */
    public function alternatives(Request $request, Customer $customer, int $productId): JsonResponse
    {
        $this->authorize('view', $customer);

        $lang = $customer->language ?: 'es';

        $product = $this->ps->getProductById($productId, $lang)
            ?? $this->psProducts->findById($productId, $lang);

        if ($product === null) {
            return response()->json(['success' => true, 'products' => []]);
        }

        $brand = $product['brand'] ?? null;
        $category = $product['category'] ?? null;

        $alternatives = [];

        if ($brand) {
            $results = $this->ps->searchProducts($brand, 8, $lang)
                ?: $this->psProducts->searchByText($brand, $lang, 8);
            $alternatives = array_values(array_filter($results, fn ($p) => (int) ($p['id'] ?? 0) !== $productId));
        }

        if (count($alternatives) < 3 && $category) {
            $results = $this->ps->searchProducts($category, 8, $lang)
                ?: $this->psProducts->searchByText($category, $lang, 8);
            $existing = array_column($alternatives, 'id');
            foreach ($results as $p) {
                if ((int) ($p['id'] ?? 0) !== $productId && ! in_array($p['id'], $existing)) {
                    $alternatives[] = $p;
                    $existing[] = $p['id'];
                }
            }
        }

        return response()->json([
            'success' => true,
            'products' => array_slice($alternatives, 0, 6),
        ]);
    }

    /**
     * Búsqueda de productos (con ?q=) o recomendados del cliente (sin ?q=).
     * Tiene en cuenta el idioma del cliente (Customer.language).
     */
    public function search(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $query = trim($request->input('q', ''));
        $lang = $customer->language ?: 'es';
        $offset = max(0, (int) $request->input('offset', 0));
        $inStockOnly = (bool) $request->input('in_stock', false);

        $products = $query !== ''
            ? $this->searchByQuery($query, $customer, $lang, $offset, $inStockOnly)
            : $this->getRecommended($customer, $lang);

        return response()->json([
            'success' => true,
            'count' => count($products),
            'has_more' => count($products) >= 10,
            'products' => $products,
        ]);
    }

    private function searchByQuery(string $query, Customer $customer, string $lang, int $offset = 0, bool $inStockOnly = false): array
    {
        // EAN13/EAN8: 8–14 numeric digits (strip spaces or dashes)
        $cleanQuery = preg_replace('/[\s\-]/', '', $query);
        if (preg_match('/^\d{8,14}$/', $cleanQuery)) {
            $product = $this->psProducts->findByEan13($cleanQuery, $lang)
                ?? $this->ps->getProductByReference($cleanQuery, $lang);
            if ($product !== null) {
                return [$product];
            }
        }

        // ID numérico puro → bridge por id_product (precios desde PS module)
        if (ctype_digit($query)) {
            return $this->searchById((int) $query, $lang);
        }

        // Referencia/SKU → bridge primero, fallback a BD directa
        if ($this->looksLikeReference($query)) {
            $product = $this->ps->getProductByReference($query, $lang);
            if ($product !== null) {
                return [$product];
            }

            $product = $this->psProducts->findByReference($query, $lang);
            if ($product !== null) {
                return [$product];
            }
        }

        // Búsqueda por texto → bridge primero (precios desde PS module)
        $results = $this->ps->searchProducts($query, 10, $lang, $offset, $inStockOnly);
        if (! empty($results)) {
            return $results;
        }

        // Fallback a BD directa si el bridge no responde
        return $this->psProducts->searchByText($query, $lang, 10, $offset, $inStockOnly);
    }

    private function searchById(int $id, string $lang): array
    {
        // Precios desde el bridge (PS module endpoint)
        $product = $this->ps->getProductById($id, $lang);
        if ($product !== null) {
            return [$product];
        }

        // Fallback a BD directa si el bridge no responde
        $product = $this->psProducts->findById($id, $lang);

        return $product !== null ? [$product] : [];
    }

    /**
     * Returns PS shipping addresses for a customer.
     */
    public function addresses(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $externalId = $this->externalId($customer);

        $addresses = ($customer->email || $externalId !== null)
            ? $this->ps->getCustomerAddresses((string) $customer->email, $externalId)
            : [];

        return response()->json(['success' => true, 'addresses' => $addresses]);
    }

    /**
     * external_id ya vinculado del cliente (mismo patrón que
     * ContactAggregatorService::prestashop()): sin él, un contacto cuyo email
     * de Helpdesk no coincide con el de su cuenta de PrestaShop no resolvía
     * ninguna dirección/devolución/cupón/mensaje.
     */
    private function externalId(Customer $customer): ?int
    {
        $externalId = $customer->externalIdFor('prestashop');

        return $externalId !== null ? (int) $externalId : null;
    }

    /**
     * Returns PS orders + carritos abandonados/en curso para un cliente. Carga
     * diferida (AJAX) desde el tab "Tienda" del inbox — evita el bloqueo
     * síncrono del bridge de PrestaShop (hasta 12s) en el render del panel
     * derecho. Ambos salen de la misma llamada a getCustomerContext() (ya
     * cacheada), así que devolver también los carritos no cuesta una llamada
     * extra al bridge.
     */
    public function orders(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $externalId = $this->externalId($customer);
        $email = (string) $customer->email;

        if ($email === '' && $externalId === null) {
            return response()->json(['success' => true, 'bridge' => 'ok', 'fetched_at' => time()] + $this->contextPayload([]));
        }

        // "Actualizar" salta la caché, pero se guarda antes lo cacheado: si el
        // puente no responde, es lo que se enseña (marcado como caché).
        $previous = $email !== '' ? $this->ps->peekCachedContext($email) : null;

        if ($request->boolean('fresh') && $email !== '') {
            $this->ps->forgetCache($email);
        }

        try {
            $context = $this->ps->getCustomerContextOrFail($email, $customer->id, $externalId);
        } catch (PsUpstreamException) {
            // El puente no responde: se devuelve lo último cacheado (si lo hay)
            // marcado como tal, para que el panel diga "mostrando caché" en vez
            // de pintar "Sin pedidos" como si el cliente no tuviera nada.
            $stale = ($previous['customer']['found'] ?? false) ? $previous : null;

            return response()->json([
                'success' => false,
                'bridge' => 'down',
                'stale' => $stale !== null,
                'message' => 'PrestaShop no responde ahora mismo.',
            ] + $this->contextPayload($stale ?? []), 503);
        }

        return response()->json([
            'success' => true,
            'bridge' => 'ok',
            'fetched_at' => $context['fetched_at'] ?? time(),
        ] + $this->contextPayload($context));
    }

    /**
     * Todo lo que el tab "Tienda" necesita sale de customer.helpdesk_context
     * (una sola llamada al bridge): cliente, pedidos, carritos, direcciones,
     * devoluciones, cupones, reembolsos, mensajes y lista de deseos.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function contextPayload(array $context): array
    {
        return [
            'customer' => $context['customer'] ?? null,
            'orders' => $context['orders'] ?? [],
            'carts' => $context['carts'] ?? [],
            'addresses' => $context['addresses'] ?? null,
            'returns' => $context['returns'] ?? null,
            'vouchers' => $context['vouchers'] ?? null,
            'refunds' => $context['refunds'] ?? null,
            'messages' => $context['messages'] ?? null,
            'wishlist' => $context['wishlist'] ?? null,
        ];
    }

    /**
     * Returns PS return (RMA) history for a customer.
     */
    public function returns(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $externalId = $this->externalId($customer);
        $returns = ($customer->email || $externalId !== null)
            ? $this->ps->getCustomerReturns((string) $customer->email, $externalId)
            : [];

        return response()->json(['success' => true, 'returns' => $returns]);
    }

    /**
     * Returns the customer's own PS vouchers/cart rules.
     */
    public function vouchers(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $externalId = $this->externalId($customer);
        $vouchers = ($customer->email || $externalId !== null)
            ? $this->ps->getCustomerVouchers((string) $customer->email, $externalId)
            : [];

        return response()->json(['success' => true, 'vouchers' => $vouchers]);
    }

    /**
     * Returns the customer's native PrestaShop message threads.
     */
    public function messages(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $externalId = $this->externalId($customer);
        $messages = ($customer->email || $externalId !== null)
            ? $this->ps->getCustomerMessages((string) $customer->email, $externalId)
            : [];

        return response()->json(['success' => true, 'messages' => $messages]);
    }

    /**
     * Returns the customer's wishlist products.
     */
    public function wishlist(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $externalId = $this->externalId($customer);
        $items = ($customer->email || $externalId !== null)
            ? $this->ps->getCustomerWishlist((string) $customer->email, $externalId)
            : [];

        return response()->json(['success' => true, 'items' => $items]);
    }

    /**
     * Returns the customer's real refunds (order_slip) — money actually
     * returned, not RMA requests (see returns()).
     */
    public function refunds(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $externalId = $this->externalId($customer);
        $refunds = ($customer->email || $externalId !== null)
            ? $this->ps->getCustomerRefunds((string) $customer->email, $externalId)
            : [];

        return response()->json(['success' => true, 'refunds' => $refunds]);
    }

    /**
     * Returns available PS categories for the search filter dropdown.
     */
    public function categories(Request $request): JsonResponse
    {
        $this->authorize('helpdeskprestashop.view');

        $lang = $request->input('lang', 'es');
        $categories = $this->ps->getCategories($lang);

        return response()->json(['success' => true, 'categories' => $categories]);
    }

    /**
     * Provincias/estados de un país — desplegable del formulario de
     * dirección (crear/editar). España (6) por defecto si no se especifica.
     */
    public function countryStates(Request $request): JsonResponse
    {
        // Las provincias hacen falta para crear direcciones: también con los
        // permisos de direcciones o de carrito, no solo con el de ver.
        $user = $request->user();
        abort_unless($user && ($user->can('helpdeskprestashop.view') || $user->can('helpdeskprestashop.addresses.manage') || $user->can('helpdeskprestashop.carts.manage')), 403);

        $countryId = (int) $request->query('id_country', 6);
        $states = $this->ps->getCountryStates($countryId);

        return response()->json(['success' => true, 'states' => $states]);
    }

    /**
     * Detecta si el query parece una referencia/SKU de producto.
     * Patrón: empieza por letra, mezcla letras+dígitos, sin espacios (ej. C112972, SKU-001).
     */
    private function looksLikeReference(string $query): bool
    {
        return (bool) preg_match('/^[A-Za-z][A-Za-z0-9\-]{1,30}$/', $query);
    }

    private function getRecommended(Customer $customer, string $lang): array
    {
        // Recomendados: productos de pedidos previos vía bridge (ya cacheado)
        if ($customer->email) {
            $products = $this->ps->getProductsFromHistory($customer->email, 6);
            if (! empty($products)) {
                return $this->enrichWithStock($products, $lang);
            }
        }

        return [];
    }

    /**
     * Reemplaza productos del historial con datos completos (precios/stock incluidos).
     * Resuelve el lote entero en una sola consulta a la BD de PrestaShop en vez de
     * una llamada HTTP secuencial al bridge por producto (hasta 6 × 12s en el peor
     * caso). Solo cae al bridge, producto a producto, para los pocos ids que el
     * batch no resuelva (p. ej. producto inactivo, o BD directa sin configurar).
     *
     * @param  array<int,array<string,mixed>>  $products
     * @return array<int,array<string,mixed>>
     */
    private function enrichWithStock(array $products, string $lang): array
    {
        $ids = array_values(array_filter(array_map(
            fn (array $p) => (int) ($p['id'] ?? $p['id_product'] ?? 0),
            $products
        )));

        $resolved = $this->psProducts->findByIds($ids, $lang);

        return array_map(function (array $p) use ($lang, $resolved): array {
            $id = (int) ($p['id'] ?? $p['id_product'] ?? 0);
            if ($id === 0) {
                return $p;
            }

            return $resolved[$id]
                ?? $this->ps->getProductById($id, $lang)
                ?? $p;
        }, $products);
    }
}
