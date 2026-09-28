<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Answers product questions the bot can't cover today with product_search /
 * product_detail: stock per size/color, "¿hay talla 44?" and comparing 2-3
 * products. Reuses the HelpdeskPrestashop Ext\CatalogService (stock per
 * location, delivery text, pricing — alsernetbridge catalog.product_sheet /
 * catalog.product_compare) and PrestashopProductQueryService (combination
 * labels and stock straight from the PS database, no bridge round trip).
 * Optional like ChatFlowOrderLookup: null methods when HelpdeskPrestashop
 * isn't installed.
 *
 * Everything returned here goes straight into the LLM prompt: compact,
 * no HTML, no internal data (costs, exact stock units per warehouse). Stock
 * is always bucketed into "disponible" / "últimas unidades" / "agotado".
 */
class ChatFlowProductInsights
{
    private const CACHE_TTL_SECONDS = 300;

    private const MAX_OPTIONS = 40;

    private const LOW_STOCK_THRESHOLD = 3;

    private const COMPARE_MIN = 2;

    private const COMPARE_MAX = 3;

    private const DESCRIPTION_MAX_LENGTH = 300;

    /**
     * @param  object|null  $catalog  HelpdeskPrestashop Ext\CatalogService (optional)
     * @param  object|null  $query  HelpdeskPrestashop PrestashopProductQueryService (optional)
     */
    public function __construct(
        private readonly ?object $catalog = null,
        private readonly ?object $query = null,
    ) {}

    /**
     * Combinations (talla/color…) of a product with stock and delivery per
     * option, plus the physical stores that have it. Max 40 options.
     *
     * @return array{product_id:int, title:string, options:array<int,array{id_product_attribute:int,label:string,available:bool,stock_level:string,delivery_text:?string}>, delivery_text:?string, in_store_stock:array<int,array{store:string,available:bool}>}|null
     */
    public function variants(int $productId, ?string $lang = null): ?array
    {
        if (! $this->query) {
            return null;
        }

        $lang = $lang ?: 'es';

        return Cache::remember(
            $this->cacheKey('variants', (string) $productId, $lang),
            self::CACHE_TTL_SECONDS,
            fn () => $this->buildVariants($productId, $lang)
        );
    }

    /**
     * Finds the combination that matches what the customer said ("44",
     * "talla 44", "marrón 44", "XL") by comparing normalized tokens against
     * each combination's attribute values. Case/accent-insensitive.
     *
     * @return array{id_product_attribute:int, label:string, available:bool, stock_level:string, delivery_text:?string}|null
     */
    public function optionFor(int $productId, string $wanted, ?string $lang = null): ?array
    {
        $lang = $lang ?: 'es';
        $combos = $this->combinationData($productId, $lang);

        if ($combos === null || $combos === []) {
            return null;
        }

        $wantedTokens = $this->normalizeTokens($wanted);
        if ($wantedTokens === []) {
            return null;
        }

        $best = $this->bestMatch($combos, $wantedTokens);
        if ($best === null) {
            return null;
        }

        $available = $best['stock'] > 0;
        $deliveryText = $available ? $this->deliveryText($productId, $best['id_product_attribute']) : null;

        return $this->buildOption($best, $deliveryText);
    }

    /**
     * Compact comparison of 2-3 products: brand, category, price,
     * availability, delivery and key variants, plus a short description
     * (max 300 chars, no HTML) for each one.
     *
     * @param  array<int, int>  $productIds
     * @return array<int, array{id:int, title:string, brand:?string, category:?string, price:?float, has_discount:bool, available:bool, stock_level:string, delivery_text:?string, variants:array, description:?string}>|null
     */
    public function compare(array $productIds, ?string $lang = null): ?array
    {
        if (! $this->catalog) {
            return null;
        }

        $ids = array_slice(array_values(array_unique(array_map('intval', $productIds))), 0, self::COMPARE_MAX);
        if (count($ids) < self::COMPARE_MIN) {
            return null;
        }

        $lang = $lang ?: 'es';
        $items = $this->compareData($ids, $lang);
        if ($items === []) {
            return null;
        }

        $descriptions = $this->descriptionsFor($ids, $lang);

        return array_map(fn (array $item) => $this->compactCompareItem($item, $descriptions), $items);
    }

    /** Delivery text for a product (or one of its combinations). */
    public function deliveryText(int $productId, int $idProductAttribute = 0): ?string
    {
        $sheet = $this->sheetData($productId, $idProductAttribute);

        return $sheet['availability']['delivery_text'] ?? null;
    }

    // ──────────────────────────────────────────────────────────────────────

    private function buildVariants(int $productId, string $lang): ?array
    {
        $product = $this->query->findById($productId, $lang);
        if ($product === null) {
            return null;
        }

        $combos = $this->combinationData($productId, $lang) ?? [];
        $sheet = $this->sheetData($productId, 0);
        $deliveryText = $sheet['availability']['delivery_text'] ?? null;

        $options = array_map(
            fn (array $combo) => $this->buildOption($combo, $deliveryText),
            array_slice($combos, 0, self::MAX_OPTIONS)
        );

        return [
            'product_id' => $productId,
            'title' => (string) ($product['name'] ?? ''),
            'options' => $options,
            'delivery_text' => $deliveryText,
            'in_store_stock' => $this->inStoreStock($sheet),
        ];
    }

    /**
     * Combinations of a product with a display label ("Talla: 44 · Color:
     * Marrón") and raw attribute value names (for token matching), sourced
     * from PrestashopProductQueryService::getProductAttributes (direct DB
     * read, no bridge round trip, no customer needed).
     *
     * @return array<int, array{id_product_attribute:int, label:string, values:array<int,string>, stock:int}>|null
     */
    private function combinationData(int $productId, string $lang): ?array
    {
        if (! $this->query) {
            return null;
        }

        return Cache::remember(
            $this->cacheKey('combos', (string) $productId, $lang),
            self::CACHE_TTL_SECONDS,
            function () use ($productId, $lang) {
                $attrs = $this->query->getProductAttributes($productId, $lang);
                $labels = $this->attributeLabels((array) ($attrs['attributes'] ?? []));

                return array_map(
                    fn (array $combo) => $this->normalizeCombo($combo, $labels),
                    (array) ($attrs['combinations'] ?? [])
                );
            }
        );
    }

    /**
     * @param  array<int, array{full:string, value:string}>  $labels
     * @return array{id_product_attribute:int, label:string, values:array<int,string>, stock:int}
     */
    private function normalizeCombo(array $combo, array $labels): array
    {
        $attributeIds = array_map('intval', (array) ($combo['attribute_ids'] ?? []));

        $pieces = array_filter(array_map(fn (int $id) => $labels[$id]['full'] ?? null, $attributeIds));
        $values = array_filter(array_map(fn (int $id) => $labels[$id]['value'] ?? null, $attributeIds));

        return [
            'id_product_attribute' => (int) ($combo['id'] ?? 0),
            'label' => implode(' · ', array_values($pieces)),
            'values' => array_values($values),
            'stock' => (int) ($combo['stock'] ?? 0),
        ];
    }

    /**
     * @param  array<int, array{group_name?:string, values?:array<int,array{id?:int,name?:string}>}>  $groups
     * @return array<int, array{full:string, value:string}> attribute id => label pieces
     */
    private function attributeLabels(array $groups): array
    {
        $labels = [];

        foreach ($groups as $group) {
            $groupName = trim((string) ($group['group_name'] ?? ''));

            foreach ((array) ($group['values'] ?? []) as $value) {
                $id = (int) ($value['id'] ?? 0);
                $valueName = trim((string) ($value['name'] ?? ''));

                if ($id === 0 || $valueName === '') {
                    continue;
                }

                $labels[$id] = [
                    'full' => $groupName !== '' ? "{$groupName}: {$valueName}" : $valueName,
                    'value' => $valueName,
                ];
            }
        }

        return $labels;
    }

    /**
     * @param  array{id_product_attribute:int, label:string, stock:int}  $combo
     * @return array{id_product_attribute:int, label:string, available:bool, stock_level:string, delivery_text:?string}
     */
    private function buildOption(array $combo, ?string $deliveryText): array
    {
        $available = $combo['stock'] > 0;

        return [
            'id_product_attribute' => $combo['id_product_attribute'],
            'label' => $combo['label'],
            'available' => $available,
            'stock_level' => $this->stockLevel($combo['stock']),
            'delivery_text' => $available ? $deliveryText : null,
        ];
    }

    /**
     * Picks the combination whose attribute values best overlap the
     * customer's words. Ties go to the option that's actually in stock.
     *
     * @param  array<int, array{id_product_attribute:int, label:string, values:array<int,string>, stock:int}>  $combos
     * @param  array<int, string>  $wantedTokens
     * @return array{id_product_attribute:int, label:string, values:array<int,string>, stock:int}|null
     */
    private function bestMatch(array $combos, array $wantedTokens): ?array
    {
        $best = null;
        $bestScore = 0;
        $bestAvailable = false;

        foreach ($combos as $combo) {
            $valueTokens = $this->normalizeTokens(implode(' ', $combo['values']));
            $score = count(array_intersect($wantedTokens, $valueTokens));

            if ($score === 0) {
                continue;
            }

            $available = $combo['stock'] > 0;
            $better = $score > $bestScore || ($score === $bestScore && $available && ! $bestAvailable);

            if ($better) {
                $best = $combo;
                $bestScore = $score;
                $bestAvailable = $available;
            }
        }

        return $best;
    }

    /** Lowercase, accent-stripped tokens for case/accent-insensitive matching. */
    private function normalizeTokens(string $text): array
    {
        $ascii = Str::ascii(mb_strtolower(trim($text)));
        preg_match_all('/[a-z0-9]+/', $ascii, $matches);

        return $matches[0];
    }

    private function stockLevel(int $stock): string
    {
        return match (true) {
            $stock <= 0 => 'agotado',
            $stock <= self::LOW_STOCK_THRESHOLD => 'últimas unidades',
            default => 'disponible',
        };
    }

    /**
     * @return array<int, array{store:string, available:bool}>
     */
    private function inStoreStock(?array $sheet): array
    {
        $locations = (array) ($sheet['stock']['locations'] ?? []);
        // "pocomaco" es el almacén central, no una tienda física visitable.
        $stores = array_filter($locations, fn (array $location) => ($location['key'] ?? '') !== 'pocomaco');

        return array_values(array_map(fn (array $location) => [
            'store' => (string) ($location['label'] ?? ''),
            'available' => ((int) ($location['units'] ?? 0)) > 0,
        ], $stores));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sheetData(int $productId, int $idProductAttribute): ?array
    {
        if (! $this->catalog) {
            return null;
        }

        return Cache::remember(
            $this->cacheKey('sheet', "{$productId}.{$idProductAttribute}", 'x'),
            self::CACHE_TTL_SECONDS,
            function () use ($productId, $idProductAttribute) {
                try {
                    return $this->catalog->productSheet(null, $productId, $idProductAttribute);
                } catch (\Throwable $e) {
                    Log::warning('ChatFlowProductInsights: productSheet failed', [
                        'product_id' => $productId,
                        'product_attribute_id' => $idProductAttribute,
                        'error' => $e->getMessage(),
                    ]);

                    return null;
                }
            }
        );
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, array<string, mixed>>
     */
    private function compareData(array $ids, string $lang): array
    {
        return Cache::remember(
            $this->cacheKey('compare', implode('-', $ids), $lang),
            self::CACHE_TTL_SECONDS,
            function () use ($ids, $lang) {
                try {
                    return $this->catalog->compare($ids, $lang);
                } catch (\Throwable $e) {
                    Log::warning('ChatFlowProductInsights: catalog compare failed', [
                        'product_ids' => $ids,
                        'error' => $e->getMessage(),
                    ]);

                    return [];
                }
            }
        );
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, string>
     */
    private function descriptionsFor(array $ids, string $lang): array
    {
        if (! $this->query) {
            return [];
        }

        $products = $this->query->findByIds($ids, $lang);

        return array_filter(array_map(
            fn (array $p) => $this->truncateDescription((string) ($p['description'] ?? '')),
            $products
        ));
    }

    private function truncateDescription(string $text): ?string
    {
        $text = trim(strip_tags($text));
        if ($text === '') {
            return null;
        }

        return mb_strlen($text) > self::DESCRIPTION_MAX_LENGTH
            ? mb_substr($text, 0, self::DESCRIPTION_MAX_LENGTH).'…'
            : $text;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<int, string>  $descriptions
     * @return array{id:int, title:string, brand:?string, category:?string, price:?float, has_discount:bool, available:bool, stock_level:string, delivery_text:?string, variants:array, description:?string}
     */
    private function compactCompareItem(array $item, array $descriptions): array
    {
        $id = (int) ($item['id'] ?? 0);
        $stock = (int) ($item['stock'] ?? 0);

        return [
            'id' => $id,
            'title' => (string) ($item['name'] ?? ''),
            'brand' => $item['brand'] ?? null,
            'category' => $item['category'] ?? null,
            'price' => isset($item['price_with_tax']) ? (float) $item['price_with_tax'] : null,
            'has_discount' => (bool) ($item['has_discount'] ?? false),
            'available' => $stock > 0,
            'stock_level' => $this->stockLevel($stock),
            'delivery_text' => $item['delivery_text'] ?? null,
            'variants' => (array) ($item['variants'] ?? []),
            'description' => $descriptions[$id] ?? null,
        ];
    }

    private function cacheKey(string $type, string $suffix, string $lang): string
    {
        return "chatflow.product_insights.{$type}.{$suffix}.{$lang}";
    }
}
