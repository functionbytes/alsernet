<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Customer;
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

        $addresses = $customer->email
            ? $this->ps->getCustomerAddresses($customer->email)
            : [];

        return response()->json(['success' => true, 'addresses' => $addresses]);
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
     * Reemplaza productos del historial con datos completos desde el bridge (precios incluidos).
     * Fallback a BD directa si el bridge no responde.
     *
     * @param  array<int,array<string,mixed>>  $products
     * @return array<int,array<string,mixed>>
     */
    private function enrichWithStock(array $products, string $lang): array
    {
        return array_map(function (array $p) use ($lang): array {
            $id = (int) ($p['id'] ?? $p['id_product'] ?? 0);
            if ($id === 0) {
                return $p;
            }

            return $this->ps->getProductById($id, $lang)
                ?? $this->psProducts->findById($id, $lang)
                ?? $p;
        }, $products);
    }
}
