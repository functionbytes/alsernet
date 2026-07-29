<?php

namespace Modules\Supplier\Services\Extraction\Drivers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Extraction\ExtractionBatch;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Services\DocumentExtractionService;
use Modules\Supplier\Services\Extraction\Contracts\SourceDriverInterface;

/**
 * API REST Driver
 *
 * Fetches product data from REST API endpoints with automatic pagination.
 * Supports Shopify-style pagination (?page=N), cursor-based pagination,
 * and Link header pagination out of the box.
 *
 * Configuration (config_type='connection'):
 *   base_url     - API base URL (e.g. https://www.slam.com)
 *   endpoint     - products endpoint path (default: /collections/all/products.json)
 *   page_param   - query param for page number (default: page)
 *   limit_param  - query param for page size (default: limit)
 *   limit        - items per page (default: 250)
 *   products_key - JSON key containing products array (default: products)
 *   auth_type    - none|bearer|basic|header
 *   auth_token   - token for bearer/header auth
 *   headers      - extra headers as key:value pairs
 */
class ApiDriver implements SourceDriverInterface
{
    private const USER_AGENT = 'Mozilla/5.0 (compatible; SupplierBot/1.0; +https://alsernet.es/bot)';

    private const DEFAULT_ENDPOINT = '/collections/all/products.json';

    private const DEFAULT_LIMIT = 250;

    public function __construct(
        private readonly DocumentExtractionService $documentService
    ) {}

    public function supports(string $sourceType): bool
    {
        return $sourceType === Source::SOURCE_TYPE_API;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function extract(Source $source, ExtractionBatch $batch): array
    {
        $config = $this->getConnectionConfig($source);

        if (! $config) {
            Log::warning('ApiDriver: no connection config found', ['source_id' => $source->id]);

            return [];
        }

        $baseUrl = rtrim($config['base_url'] ?? '', '/');

        if (! $baseUrl) {
            Log::warning('ApiDriver: base_url not configured', ['source_id' => $source->id]);

            return [];
        }

        $client = $this->buildClient($config);

        $endpoint = $config['endpoint'] ?? self::DEFAULT_ENDPOINT;
        $pageParam = $config['page_param'] ?? 'page';
        $limitParam = $config['limit_param'] ?? 'limit';
        $limit = (int) ($config['limit'] ?? self::DEFAULT_LIMIT);
        $productsKey = $config['products_key'] ?? null;

        $products = [];
        $page = 1;
        $lastCount = $limit;

        while ($lastCount >= $limit) {
            $url = $baseUrl.$endpoint;

            try {
                $response = $client->get($url, ['query' => [$pageParam => $page, $limitParam => $limit]]);
                $body = json_decode((string) $response->getBody(), true);

                if (! is_array($body)) {
                    Log::error('ApiDriver: invalid JSON response', ['url' => $url, 'page' => $page]);
                    break;
                }

                $items = $this->extractItemsFromResponse($body, $productsKey);
                $lastCount = count($items);

                if ($lastCount === 0) {
                    break;
                }

                $products = array_merge(
                    $products,
                    $this->mapItems($items, $baseUrl, $source->extraction_mode ?? Source::EXTRACTION_MODE_AI)
                );

                Log::info('ApiDriver: fetched page', [
                    'source_id' => $source->id,
                    'page' => $page,
                    'count' => $lastCount,
                    'total_so_far' => count($products),
                ]);

                $page++;

            } catch (RequestException $e) {
                Log::error('ApiDriver: request failed', [
                    'source_id' => $source->id,
                    'url' => $url,
                    'page' => $page,
                    'error' => $e->getMessage(),
                ]);
                break;
            } catch (\Exception $e) {
                Log::error('ApiDriver: unexpected error', [
                    'source_id' => $source->id,
                    'page' => $page,
                    'error' => $e->getMessage(),
                ]);
                break;
            }
        }

        Log::info('ApiDriver: extraction complete', [
            'source_id' => $source->id,
            'total_products' => count($products),
        ]);

        return $products;
    }

    /**
     * Locate the products array inside the API response.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractItemsFromResponse(array $body, ?string $productsKey): array
    {
        // Explicit key configured
        if ($productsKey && isset($body[$productsKey]) && is_array($body[$productsKey])) {
            return $body[$productsKey];
        }

        // Auto-detect: first array value that looks like a product list
        foreach (['products', 'items', 'data', 'results', 'records'] as $key) {
            if (isset($body[$key]) && is_array($body[$key]) && array_is_list($body[$key])) {
                return $body[$key];
            }
        }

        // Response is itself a list
        if (array_is_list($body)) {
            return $body;
        }

        return [];
    }

    /**
     * Map raw API items to the standard product structure.
     *
     * Detects Shopify structure automatically (has 'variants' and 'handle').
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function mapItems(array $items, string $baseUrl, string $extractionMode): array
    {
        $isShopify = isset($items[0]['handle']) && isset($items[0]['variants']);

        return array_map(fn ($item) => $isShopify
            ? $this->mapShopifyProduct($item, $baseUrl)
            : $this->mapGenericProduct($item),
            $items
        );
    }

    /**
     * Map a Shopify product to the standard structure.
     *
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    private function mapShopifyProduct(array $product, string $baseUrl): array
    {
        $variants = $product['variants'] ?? [];
        $images = $product['images'] ?? [];

        // Use first non-empty SKU as reference
        $reference = null;
        $minPrice = null;
        $variantList = [];

        foreach ($variants as $v) {
            if ($reference === null && ! empty($v['sku'])) {
                $reference = $v['sku'];
            }

            $price = isset($v['price']) ? (float) $v['price'] : null;

            if ($price !== null && ($minPrice === null || $price < $minPrice)) {
                $minPrice = $price;
            }

            $variantList[] = [
                'title' => $v['title'] ?? null,
                'sku' => $v['sku'] ?? null,
                'price' => $price,
                'compare_at_price' => isset($v['compare_at_price']) ? (float) $v['compare_at_price'] : null,
                'available' => (bool) ($v['available'] ?? false),
                'option1' => $v['option1'] ?? null,
                'option2' => $v['option2'] ?? null,
                'option3' => $v['option3'] ?? null,
            ];
        }

        $imageUrls = array_values(array_filter(array_map(
            fn ($img) => $img['src'] ?? null,
            $images
        )));

        $options = array_map(fn ($o) => $o['name'] ?? '', $product['options'] ?? []);
        $rawTags = $product['tags'] ?? [];
        $tags = is_array($rawTags)
            ? $rawTags
            : array_filter(array_map('trim', explode(',', (string) $rawTags)));

        return [
            'reference' => $reference,
            'ean' => null,
            'name' => $product['title'] ?? null,
            'description' => isset($product['body_html']) ? strip_tags($product['body_html']) : null,
            'price' => $minPrice,
            'currency' => 'EUR',
            'images' => $imageUrls,
            'category' => $product['product_type'] ?? null,
            'attributes' => [
                'vendor' => $product['vendor'] ?? null,
                'product_type' => $product['product_type'] ?? null,
                'tags' => array_values($tags),
                'options' => $options,
                'variants_count' => count($variants),
                'variants' => $variantList,
                'shopify_id' => $product['id'] ?? null,
            ],
            'source_url' => isset($product['handle'])
                ? rtrim($baseUrl, '/').'/products/'.$product['handle']
                : null,
        ];
    }

    /**
     * Map a generic REST API product (best-effort field detection).
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function mapGenericProduct(array $item): array
    {
        return [
            'reference' => $item['sku'] ?? $item['reference'] ?? $item['code'] ?? $item['id'] ?? null,
            'ean' => $item['ean'] ?? $item['barcode'] ?? $item['gtin'] ?? null,
            'name' => $item['name'] ?? $item['title'] ?? $item['product_name'] ?? null,
            'description' => $item['description'] ?? $item['body_html'] ?? $item['summary'] ?? null,
            'price' => isset($item['price']) ? (float) $item['price'] : null,
            'currency' => $item['currency'] ?? $item['currency_code'] ?? 'EUR',
            'images' => (array) ($item['images'] ?? $item['image_urls'] ?? []),
            'category' => $item['category'] ?? $item['type'] ?? $item['product_type'] ?? null,
            'attributes' => array_diff_key($item, array_flip([
                'sku', 'reference', 'code', 'id', 'ean', 'barcode', 'gtin',
                'name', 'title', 'product_name', 'description', 'body_html',
                'price', 'currency', 'images', 'image_urls', 'category', 'type',
            ])),
            'source_url' => $item['url'] ?? $item['link'] ?? $item['product_url'] ?? null,
        ];
    }

    /**
     * Build Guzzle client with optional authentication.
     */
    private function buildClient(array $config): Client
    {
        $headers = ['User-Agent' => self::USER_AGENT, 'Accept' => 'application/json'];

        $authType = $config['auth_type'] ?? 'none';

        if ($authType === 'bearer' && ! empty($config['auth_token'])) {
            $headers['Authorization'] = 'Bearer '.$config['auth_token'];
        } elseif ($authType === 'header' && ! empty($config['auth_header']) && ! empty($config['auth_token'])) {
            $headers[$config['auth_header']] = $config['auth_token'];
        }

        // Merge extra headers from config
        foreach ($config['headers'] ?? [] as $name => $value) {
            $headers[$name] = $value;
        }

        return new Client([
            'timeout' => (int) ($config['timeout'] ?? 30),
            'verify' => (bool) ($config['verify_ssl'] ?? true),
            'headers' => $headers,
            'auth' => $authType === 'basic' && ! empty($config['auth_user'])
                ? [$config['auth_user'], $config['auth_token'] ?? '']
                : null,
        ]);
    }

    /**
     * Get the connection configuration for this source.
     *
     * @return array<string, mixed>|null
     */
    private function getConnectionConfig(Source $source): ?array
    {
        $config = $source->configurations()
            ->where('config_type', 'connection')
            ->where('is_enabled', true)
            ->first();

        return $config?->config_data;
    }
}
