<?php

namespace Modules\HelpdeskPrestashop\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Queries PrestaShop product data directly from the PS database.
 * Bypasses the alsernetbridge HTTP API for product searches.
 */
class PrestashopProductQueryService
{
    private string $db;

    private string $prefix;

    public function __construct()
    {
        $this->db = (string) config('helpdeskprestashop.ps_db', 'alvarez_cristia');
        $this->prefix = (string) config('helpdeskprestashop.ps_prefix', 'aalv_');
    }

    /** Search products by name or reference (partial match). */
    public function searchByText(string $query, string $lang = 'es', int $limit = 10, int $offset = 0, bool $inStockOnly = false): array
    {
        $storeUrl = $this->getStoreUrl();

        $rows = $this->baseQuery($lang)
            ->where('p.active', 1)
            ->where(function ($q) use ($query) {
                $q->where('pl.name', 'like', "%{$query}%")
                    ->orWhere('p.reference', 'like', "%{$query}%");
            })
            ->when($inStockOnly, fn ($q) => $q->where('s.quantity', '>', 0))
            ->orderBy('p.id_product')
            ->offset($offset)
            ->limit($limit)
            ->get();

        // Resolve specific_price data for the whole batch in two aggregate queries
        // instead of two queries per product (N+1).
        $productIds = $rows->pluck('id_product')->map(fn ($id) => (int) $id)->all();
        $effectivePrices = $this->resolveEffectivePriceMap($productIds);
        $specificPrices = $this->resolveSpecificPriceMap($productIds);

        return $rows->map(function ($r) use ($storeUrl, $effectivePrices, $specificPrices) {
            $row = (array) $r;
            $id = (int) ($row['id_product'] ?? 0);

            return $this->normalize(
                $row,
                $storeUrl,
                $effectivePrices[$id] ?? null,
                $specificPrices[$id] ?? null,
            );
        })->toArray();
    }

    /** Find a product by exact SKU/reference. */
    public function findByReference(string $reference, string $lang = 'es'): ?array
    {
        return $this->findByField('p.reference', $reference, $lang);
    }

    /** Find a product by EAN13/EAN8 barcode. */
    public function findByEan13(string $ean, string $lang = 'es'): ?array
    {
        return $this->findByField('p.ean13', $ean, $lang);
    }

    /** Find a product by PrestaShop product ID. */
    public function findById(int $id, string $lang = 'es'): ?array
    {
        return $this->findByField('p.id_product', $id, $lang);
    }

    /**
     * Return grouped attribute values and combination stock for a product.
     * Result: ['attributes' => [...groups...], 'combinations' => [...combos...]]
     */
    public function getProductAttributes(int $productId, string $lang = 'es'): array
    {
        $langId = $this->getLangId($lang);
        $db = $this->db;
        $pfx = $this->prefix;
        $storeUrl = $this->getStoreUrl();

        $rows = DB::table("{$db}.{$pfx}product_attribute as pa")
            ->join("{$db}.{$pfx}product_attribute_combination as pac",
                'pa.id_product_attribute', '=', 'pac.id_product_attribute')
            ->join("{$db}.{$pfx}attribute as a",
                'pac.id_attribute', '=', 'a.id_attribute')
            ->join("{$db}.{$pfx}attribute_group as ag",
                'a.id_attribute_group', '=', 'ag.id_attribute_group')
            ->join("{$db}.{$pfx}attribute_group_lang as agl", function ($j) use ($langId) {
                $j->on('ag.id_attribute_group', '=', 'agl.id_attribute_group')
                    ->where('agl.id_lang', '=', $langId);
            })
            ->join("{$db}.{$pfx}attribute_lang as al", function ($j) use ($langId) {
                $j->on('a.id_attribute', '=', 'al.id_attribute')
                    ->where('al.id_lang', '=', $langId);
            })
            ->leftJoin("{$db}.{$pfx}stock_available as s", function ($j) {
                $j->on('pa.id_product', '=', 's.id_product')
                    ->on('pa.id_product_attribute', '=', 's.id_product_attribute')
                    ->where('s.id_shop', '=', 1);
            })
            ->leftJoin("{$db}.{$pfx}product_attribute_image as pai",
                'pa.id_product_attribute', '=', 'pai.id_product_attribute')
            ->where('pa.id_product', $productId)
            ->select(
                'ag.id_attribute_group', 'agl.name as group_name', 'ag.is_color_group',
                'ag.position as group_pos',
                'a.id_attribute', 'al.name as attr_name', 'a.color', 'a.position as attr_pos',
                'pac.id_product_attribute', 'pa.reference as comb_ref', 'pa.price as comb_price',
                DB::raw('COALESCE(s.quantity, 0) as comb_stock'),
                'pai.id_image as combo_image_id'
            )
            ->orderBy('ag.position')
            ->orderBy('a.position')
            ->get();

        if ($rows->isEmpty()) {
            return ['attributes' => [], 'combinations' => []];
        }

        $groups = [];
        $groupValues = [];  // gid => [aid, ...]
        $combinations = [];

        foreach ($rows as $r) {
            $gid = (int) $r->id_attribute_group;
            $aid = (int) $r->id_attribute;
            $cid = (int) $r->id_product_attribute;

            if (! isset($groups[$gid])) {
                $groups[$gid] = [
                    'group_id' => $gid,
                    'group_name' => $r->group_name,
                    'is_color' => (bool) $r->is_color_group,
                    'values' => [],
                ];
                $groupValues[$gid] = [];
            }

            if (! in_array($aid, $groupValues[$gid], true)) {
                $groups[$gid]['values'][] = [
                    'id' => $aid,
                    'name' => $r->attr_name,
                    'color' => ($r->color && $r->color !== '0') ? '#'.ltrim($r->color, '#') : null,
                ];
                $groupValues[$gid][] = $aid;
            }

            if (! isset($combinations[$cid])) {
                $stock = (int) $r->comb_stock;
                $combinations[$cid] = [
                    'id' => $cid,
                    'reference' => ($r->comb_ref !== '' && $r->comb_ref !== null) ? $r->comb_ref : null,
                    'price_delta' => (float) ($r->comb_price ?? 0),
                    'attribute_ids' => [],
                    'stock' => $stock,
                    'in_stock' => $stock > 0,
                    'image' => isset($r->combo_image_id) && $r->combo_image_id
                        ? $this->buildImageUrl($storeUrl, (int) $r->combo_image_id)
                        : null,
                ];
            }

            $combinations[$cid]['attribute_ids'][] = $aid;
        }

        // Build aid→name and aid→color lookups for combination labels
        $attrNames = [];
        $attrColors = [];
        foreach ($groups as $g) {
            foreach ($g['values'] as $v) {
                $attrNames[$v['id']] = $v['name'];
                $attrColors[$v['id']] = $v['color'];
            }
        }

        foreach ($combinations as &$combo) {
            $names = array_map(fn (int $aid) => $attrNames[$aid] ?? '', $combo['attribute_ids']);
            $combo['label'] = implode(' · ', array_filter($names));

            $combo['color'] = null;
            foreach ($combo['attribute_ids'] as $aid) {
                if (isset($attrColors[$aid]) && $attrColors[$aid] !== null) {
                    $combo['color'] = $attrColors[$aid];
                    break;
                }
            }
        }
        unset($combo);

        return [
            'attributes' => array_values($groups),
            'combinations' => array_values($combinations),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function findByField(string $column, mixed $value, string $lang): ?array
    {
        $storeUrl = $this->getStoreUrl();

        $row = $this->baseQuery($lang)
            ->where('p.active', 1)
            ->where($column, $value)
            ->first();

        return $row ? $this->normalize((array) $row, $storeUrl) : null;
    }

    /** Build the shared base query with all required joins. */
    private function baseQuery(string $lang): Builder
    {
        $langId = $this->getLangId($lang);
        $db = $this->db;
        $pfx = $this->prefix;

        return DB::table("{$db}.{$pfx}product as p")
            ->join("{$db}.{$pfx}product_lang as pl", function ($j) use ($langId) {
                $j->on('p.id_product', '=', 'pl.id_product')
                    ->where('pl.id_lang', '=', $langId)
                    ->where('pl.id_shop', '=', 1);
            })
            ->leftJoin("{$db}.{$pfx}image as i", function ($j) {
                $j->on('p.id_product', '=', 'i.id_product')
                    ->where('i.cover', '=', 1);
            })
            ->leftJoin("{$db}.{$pfx}stock_available as s", function ($j) {
                $j->on('p.id_product', '=', 's.id_product')
                    ->where('s.id_product_attribute', '=', 0)
                    ->where('s.id_shop', '=', 1);
            })
            ->leftJoin("{$db}.{$pfx}manufacturer as mfr", 'p.id_manufacturer', '=', 'mfr.id_manufacturer')
            ->leftJoin("{$db}.{$pfx}category_lang as cl", function ($j) use ($langId) {
                $j->on('p.id_category_default', '=', 'cl.id_category')
                    ->where('cl.id_lang', '=', $langId)
                    ->where('cl.id_shop', '=', 1);
            })
            ->select(
                'p.id_product', 'p.reference', 'p.ean13', 'p.price', 'p.id_tax_rules_group',
                'pl.name', 'pl.link_rewrite', 'pl.description_short',
                'i.id_image',
                'mfr.name as manufacturer_name',
                'cl.name as category_name',
                DB::raw('COALESCE(s.quantity, 0) as stock')
            );
    }

    /** Resolve ISO language code to PS id_lang (cached per request). */
    private function getLangId(string $iso): int
    {
        static $cache = [];

        if (isset($cache[$iso])) {
            return $cache[$iso];
        }

        $id = DB::table("{$this->db}.{$this->prefix}lang")
            ->where('iso_code', $iso)
            ->where('active', 1)
            ->value('id_lang');

        $cache[$iso] = $id ? (int) $id : 1;

        return $cache[$iso];
    }

    /** Derive the public store base URL from the configured API URL. */
    private function getStoreUrl(): string
    {
        $apiUrl = (string) config('helpdeskprestashop.api_url', '');

        if ($apiUrl === '') {
            return '';
        }

        $pos = strpos($apiUrl, '/modules/');

        return $pos !== false ? rtrim(substr($apiUrl, 0, $pos), '/') : rtrim($apiUrl, '/');
    }

    /** Build the PS product page URL. */
    private function buildProductUrl(string $storeUrl, int $idProduct, string $linkRewrite): string
    {
        if ($storeUrl === '' || $linkRewrite === '') {
            return '';
        }

        return "{$storeUrl}/{$idProduct}-{$linkRewrite}";
    }

    /**
     * Build the PS cover image URL.
     * PS organizes images as: /img/p/{d1}/{d2}/{d3}/.../{id_image}.jpg
     */
    private function buildImageUrl(string $storeUrl, int $imageId): string
    {
        if ($storeUrl === '') {
            return '';
        }

        $parts = str_split((string) $imageId);
        $path = implode('/', $parts);

        return "{$storeUrl}/img/p/{$path}/{$imageId}.jpg";
    }

    /** Strip HTML and truncate a PS short description. */
    private function cleanDescription(?string $html, int $maxLength = 200): ?string
    {
        if ($html === null || $html === '') {
            return null;
        }

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', trim($text));

        return mb_strlen($text) > $maxLength
            ? mb_substr($text, 0, $maxLength).'…'
            : $text;
    }

    /**
     * Normalize a DB row to the frontend product array.
     *
     * When searching in batches, callers pass pre-resolved specific_price data
     * to avoid per-product queries. Single-row callers leave them null and the
     * values are resolved lazily (one product → no N+1).
     *
     * @param  float|null  $effectivePrice  pre-resolved effective price (or null to resolve lazily)
     * @param  array{reduction: float, reduction_type: string}|null  $specificPrice  pre-resolved discount row
     */
    private function normalize(array $r, string $storeUrl, ?float $effectivePrice = null, ?array $specificPrice = null): array
    {
        $idProduct = (int) ($r['id_product'] ?? 0);
        $linkRewrite = (string) ($r['link_rewrite'] ?? '');
        $imageId = isset($r['id_image']) && $r['id_image'] !== null ? (int) $r['id_image'] : null;
        $stock = (int) ($r['stock'] ?? 0);
        $ean13 = ($r['ean13'] ?? '') ?: null;
        $price = $this->getEffectivePrice($idProduct, (float) ($r['price'] ?? 0), $effectivePrice);
        $taxRulesGroupId = (int) ($r['id_tax_rules_group'] ?? 0);
        $taxRate = $taxRulesGroupId > 0 ? $this->getTaxRate($taxRulesGroupId) : 0.0;

        return [
            'id' => $idProduct,
            'name' => (string) ($r['name'] ?? ''),
            'sku' => ($r['reference'] ?? '') !== '' ? (string) $r['reference'] : null,
            'ean13' => ($ean13 !== '0' && $ean13 !== null) ? $ean13 : null,
            'price' => $price,
            'tax_rate' => $taxRate,
            'price_with_tax' => $taxRate > 0 ? round($price * (1 + $taxRate / 100), 2) : $price,
            'price_original' => $this->getPriceOriginal($idProduct, $price, $taxRate, $specificPrice),
            'has_discount' => false,
            'description' => $this->cleanDescription($r['description_short'] ?? null),
            'image' => $imageId ? $this->buildImageUrl($storeUrl, $imageId) : null,
            'url' => $this->buildProductUrl($storeUrl, $idProduct, $linkRewrite),
            'stock' => $stock,
            'in_stock' => $stock > 0,
            'brand' => ($r['manufacturer_name'] ?? '') ?: null,
            'category' => ($r['category_name'] ?? '') ?: null,
        ];
    }

    /**
     * Resolve the effective price for a product.
     * PS stores price=0 when the real price lives in aalv_specific_price.
     *
     * @param  float|null  $resolved  pre-resolved effective price from a batch lookup
     */
    private function getEffectivePrice(int $productId, float $basePrice, ?float $resolved = null): float
    {
        if ($basePrice > 0) {
            return $basePrice;
        }

        if ($resolved !== null) {
            return $resolved;
        }

        static $cache = [];

        if (isset($cache[$productId])) {
            return $cache[$productId];
        }

        $price = $this->queryEffectivePrice($productId);
        $cache[$productId] = $price;

        return $price;
    }

    /** Single-product effective price lookup against specific_price. */
    private function queryEffectivePrice(int $productId): float
    {
        $db = $this->db;
        $pfx = $this->prefix;

        try {
            $price = DB::table("{$db}.{$pfx}specific_price")
                ->where('id_product', $productId)
                ->where('from_quantity', '<=', 1)
                ->where('price', '>', 0)
                ->where(function ($q) {
                    $q->where('from', '0000-00-00 00:00:00')
                        ->orWhere('from', '<=', DB::raw('NOW()'));
                })
                ->where(function ($q) {
                    $q->where('to', '0000-00-00 00:00:00')
                        ->orWhere('to', '>=', DB::raw('NOW()'));
                })
                ->orderBy('id_product_attribute')
                ->orderByDesc('id_specific_price')
                ->value('price');

            return $price !== null ? (float) $price : 0.0;
        } catch (\Throwable) {
            return 0.0;
        }
    }

    /**
     * Resolve effective prices for many products in a single query.
     * Mirrors queryEffectivePrice() ordering (id_product_attribute ASC, id_specific_price DESC):
     * the first valid row encountered per product wins.
     *
     * @param  int[]  $productIds
     * @return array<int, float> product id => effective price
     */
    private function resolveEffectivePriceMap(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter($productIds)));

        if ($productIds === []) {
            return [];
        }

        $db = $this->db;
        $pfx = $this->prefix;

        try {
            $rows = DB::table("{$db}.{$pfx}specific_price")
                ->whereIn('id_product', $productIds)
                ->where('from_quantity', '<=', 1)
                ->where('price', '>', 0)
                ->where(function ($q) {
                    $q->where('from', '0000-00-00 00:00:00')
                        ->orWhere('from', '<=', DB::raw('NOW()'));
                })
                ->where(function ($q) {
                    $q->where('to', '0000-00-00 00:00:00')
                        ->orWhere('to', '>=', DB::raw('NOW()'));
                })
                ->orderBy('id_product')
                ->orderBy('id_product_attribute')
                ->orderByDesc('id_specific_price')
                ->get(['id_product', 'price']);
        } catch (\Throwable) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $id = (int) $row->id_product;
            if (! isset($map[$id])) {
                $map[$id] = (float) $row->price;
            }
        }

        return $map;
    }

    /**
     * Resolve discount rows for many products in a single query.
     * Mirrors getPriceOriginal()'s filter/ordering (id_specific_price DESC):
     * the first row encountered per product wins.
     *
     * @param  int[]  $productIds
     * @return array<int, array{reduction: float, reduction_type: string}>
     */
    private function resolveSpecificPriceMap(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter($productIds)));

        if ($productIds === []) {
            return [];
        }

        $db = $this->db;
        $pfx = $this->prefix;

        try {
            $rows = DB::table("{$db}.{$pfx}specific_price")
                ->whereIn('id_product', $productIds)
                ->where('from_quantity', '<=', 1)
                ->whereIn('id_group', [0, 1])
                ->whereIn('id_currency', [0, 1])
                ->where(function ($q) {
                    $q->where('from', '0000-00-00 00:00:00')
                        ->orWhere('from', '<=', DB::raw('NOW()'));
                })
                ->where(function ($q) {
                    $q->where('to', '0000-00-00 00:00:00')
                        ->orWhere('to', '>=', DB::raw('NOW()'));
                })
                ->orderBy('id_product')
                ->orderByDesc('id_specific_price')
                ->get(['id_product', 'reduction', 'reduction_type']);
        } catch (\Throwable) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $id = (int) $row->id_product;
            if (! isset($map[$id])) {
                $map[$id] = [
                    'reduction' => (float) ($row->reduction ?? 0),
                    'reduction_type' => (string) ($row->reduction_type ?? 'amount'),
                ];
            }
        }

        return $map;
    }

    /**
     * Look up the pre-discount (original) price from specific_price.
     * Returns the original price with tax applied, or null when no discount exists.
     *
     * @param  array{reduction: float, reduction_type: string}|null  $resolved  pre-resolved discount row from a batch lookup
     */
    private function getPriceOriginal(int $productId, float $effectivePrice, float $taxRate, ?array $resolved = null): ?float
    {
        try {
            $reductionRow = $resolved ?? $this->queryDiscountRow($productId);

            if ($reductionRow === null || $reductionRow['reduction'] <= 0) {
                return null;
            }

            $reduction = $reductionRow['reduction'];
            $type = $reductionRow['reduction_type'];
            $basePrice = $effectivePrice; // without VAT

            $originalBase = $type === 'amount'
                ? $basePrice + $reduction
                : ($reduction < 100 ? $basePrice / (1 - $reduction / 100) : null);

            if ($originalBase === null) {
                return null;
            }

            return $taxRate > 0 ? round($originalBase * (1 + $taxRate / 100), 2) : round($originalBase, 2);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Single-product discount row lookup against specific_price.
     *
     * @return array{reduction: float, reduction_type: string}|null
     */
    private function queryDiscountRow(int $productId): ?array
    {
        $db = $this->db;
        $pfx = $this->prefix;

        $row = DB::selectOne(
            "SELECT reduction, reduction_type
             FROM {$db}.{$pfx}specific_price
             WHERE id_product = ?
               AND from_quantity <= 1
               AND id_group IN (0, 1)
               AND id_currency IN (0, 1)
               AND (`from` = '0000-00-00 00:00:00' OR `from` <= NOW())
               AND (`to`   = '0000-00-00 00:00:00' OR `to`   >= NOW())
             ORDER BY id_specific_price DESC
             LIMIT 1",
            [$productId]
        );

        if (! $row) {
            return null;
        }

        return [
            'reduction' => (float) ($row->reduction ?? 0),
            'reduction_type' => (string) ($row->reduction_type ?? 'amount'),
        ];
    }

    /** Resolve the VAT rate for a tax rules group (cached per request). */
    private function getTaxRate(int $taxRulesGroupId): float
    {
        static $cache = [];

        if (isset($cache[$taxRulesGroupId])) {
            return $cache[$taxRulesGroupId];
        }

        $db = $this->db;
        $pfx = $this->prefix;

        try {
            $rate = DB::table("{$db}.{$pfx}tax_rule as txr")
                ->join("{$db}.{$pfx}tax as tx", function ($j) {
                    $j->on('txr.id_tax', '=', 'tx.id_tax')
                        ->where('tx.active', '=', 1);
                })
                ->where('txr.id_tax_rules_group', $taxRulesGroupId)
                ->orderBy('txr.id_state')
                ->value('tx.rate');

            $cache[$taxRulesGroupId] = $rate !== null ? (float) $rate : 0.0;
        } catch (\Throwable) {
            $cache[$taxRulesGroupId] = 0.0;
        }

        return $cache[$taxRulesGroupId];
    }
}
