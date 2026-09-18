<?php

namespace Modules\Supplier\Services\Extraction;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Traits\ValidatesPublicUrl;

/**
 * Source Detection Service
 *
 * Probes a URL to detect the platform type and returns a recommended
 * source configuration so the form can be pre-filled automatically.
 *
 * Detection priority:
 *  1. Known API endpoints (Shopify, WooCommerce, PrestaShop, Magento)
 *  2. Response headers (X-Shopify-Shop-Api-Call-Limit, X-Powered-By, etc.)
 *  3. HTML signatures (<meta generator>, window.Shopify, etc.)
 *  4. Falls back to "website" (static scraping)
 */
class SourceDetectionService
{
    use ValidatesPublicUrl;

    private const USER_AGENT = 'Mozilla/5.0 (compatible; SupplierBot/1.0; +https://alsernet.es/bot)';

    private const TIMEOUT = 10;

    /**
     * Probe a URL and return detection result.
     *
     * @return array{
     *   platform: string,
     *   source_type: string,
     *   confidence: string,
     *   label: string,
     *   description: string,
     *   config: array<string, mixed>,
     *   stats: array<string, mixed>
     * }
     */
    public function detect(string $url): array
    {
        $url = $this->normaliseUrl($url);
        $base = $this->extractBaseUrl($url);

        $this->assertUrlIsPublic($url);

        $client = new Client([
            'timeout' => self::TIMEOUT,
            // verify=false is intentional. This service probes arbitrary
            // supplier URLs that frequently use self-signed or expired certs
            // (typical of ERP/internal portals). SSRF protection above already
            // blocks private/internal targets, so the cost of accepting an
            // invalid cert here is bounded to a one-shot detection request.
            'verify' => false,
            'headers' => [
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'text/html,application/json,*/*',
            ],
            'allow_redirects' => ['max' => 5, 'track_redirects' => true],
        ]);

        // Try API platforms first (definitive detection)
        $result = $this->detectShopify($client, $base)
            ?? $this->detectWooCommerce($client, $base)
            ?? $this->detectPrestaShop($client, $base)
            ?? $this->detectMagento($client, $base)
            ?? $this->detectFromHtml($client, $url, $base);

        Log::info('SourceDetectionService: detection complete', [
            'url' => $url,
            'platform' => $result['platform'],
            'confidence' => $result['confidence'],
        ]);

        return $result;
    }

    // -------------------------------------------------------------------------
    // Platform detectors
    // -------------------------------------------------------------------------

    private function detectShopify(Client $client, string $base): ?array
    {
        try {
            $r = $client->get($base.'/collections/all/products.json?limit=5&page=1');

            if ($r->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode((string) $r->getBody(), true);

            if (! isset($data['products']) || ! is_array($data['products'])) {
                return null;
            }

            $total = $this->countShopifyProducts($client, $base);

            return [
                'platform' => 'Shopify',
                'source_type' => 'api',
                'confidence' => 'high',
                'label' => 'Shopify — API REST',
                'description' => "Tienda Shopify detectada. {$total} productos disponibles vía API JSON.",
                'config' => [
                    'base_url' => $base,
                    'endpoint' => '/collections/all/products.json',
                    'page_param' => 'page',
                    'limit_param' => 'limit',
                    'limit' => 250,
                    'products_key' => 'products',
                    'timeout' => 30,
                    'verify_ssl' => true,
                ],
                'stats' => ['total_products' => $total],
            ];
        } catch (\Exception) {
            return null;
        }
    }

    private function detectWooCommerce(Client $client, string $base): ?array
    {
        try {
            $r = $client->get($base.'/wp-json/wc/v3/products?per_page=5&page=1');

            if ($r->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode((string) $r->getBody(), true);

            if (! is_array($data) || ! array_is_list($data)) {
                return null;
            }

            $total = (int) ($r->getHeader('X-WP-Total')[0] ?? 0);

            return [
                'platform' => 'WooCommerce',
                'source_type' => 'api',
                'confidence' => 'high',
                'label' => 'WooCommerce — API REST',
                'description' => "Tienda WooCommerce detectada. {$total} productos disponibles vía API.",
                'config' => [
                    'base_url' => $base,
                    'endpoint' => '/wp-json/wc/v3/products',
                    'page_param' => 'page',
                    'limit_param' => 'per_page',
                    'limit' => 100,
                    'products_key' => null,
                    'timeout' => 30,
                    'verify_ssl' => true,
                ],
                'stats' => ['total_products' => $total],
            ];
        } catch (\Exception) {
            return null;
        }
    }

    private function detectPrestaShop(Client $client, string $base): ?array
    {
        try {
            $r = $client->get($base.'/api/products?output_format=JSON&limit=1');

            if ($r->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode((string) $r->getBody(), true);

            if (! isset($data['products'])) {
                return null;
            }

            return [
                'platform' => 'PrestaShop',
                'source_type' => 'api',
                'confidence' => 'high',
                'label' => 'PrestaShop — API REST',
                'description' => 'Tienda PrestaShop detectada. Requiere API key para acceso completo.',
                'config' => [
                    'base_url' => $base,
                    'endpoint' => '/api/products',
                    'page_param' => 'page',
                    'limit_param' => 'limit',
                    'limit' => 100,
                    'products_key' => 'products',
                    'timeout' => 30,
                    'verify_ssl' => true,
                ],
                'stats' => [],
            ];
        } catch (\Exception) {
            return null;
        }
    }

    private function detectMagento(Client $client, string $base): ?array
    {
        try {
            $r = $client->get($base.'/rest/V1/products?searchCriteria[pageSize]=1');

            if ($r->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode((string) $r->getBody(), true);

            if (! isset($data['items'])) {
                return null;
            }

            $total = (int) ($data['total_count'] ?? 0);

            return [
                'platform' => 'Magento',
                'source_type' => 'api',
                'confidence' => 'high',
                'label' => 'Magento — API REST',
                'description' => "Tienda Magento detectada. {$total} productos disponibles.",
                'config' => [
                    'base_url' => $base,
                    'endpoint' => '/rest/V1/products',
                    'page_param' => 'searchCriteria[currentPage]',
                    'limit_param' => 'searchCriteria[pageSize]',
                    'limit' => 100,
                    'products_key' => 'items',
                    'timeout' => 30,
                    'verify_ssl' => true,
                ],
                'stats' => ['total_products' => $total],
            ];
        } catch (\Exception) {
            return null;
        }
    }

    private function detectFromHtml(Client $client, string $url, string $base): array
    {
        try {
            $r = $client->get($url);
            $html = (string) $r->getBody();
            $htmlLower = strtolower($html);
            $headers = $r->getHeaders();

            // Check response headers
            $poweredBy = strtolower($headers['X-Powered-By'][0] ?? '');
            $server = strtolower($headers['Server'][0] ?? '');

            if (str_contains($poweredBy, 'shopify') || str_contains($server, 'shopify')) {
                return $this->fallbackWebsite($base, 'Shopify (HTML)', 'medium');
            }

            // Check HTML signatures
            if (str_contains($html, 'window.Shopify') || str_contains($html, 'cdn.shopify.com')) {
                return $this->fallbackWebsite($base, 'Shopify (JS)', 'medium');
            }

            if (str_contains($htmlLower, 'wp-content') && str_contains($htmlLower, 'woocommerce')) {
                return $this->fallbackWebsite($base, 'WooCommerce (HTML)', 'medium');
            }

            if (str_contains($htmlLower, 'var prestashop') || str_contains($htmlLower, 'prestashop')) {
                return $this->fallbackWebsite($base, 'PrestaShop (HTML)', 'medium');
            }

            if (preg_match('/<meta[^>]+generator[^>]+Magento/i', $html) || str_contains($htmlLower, '/mage/') || preg_match('/magento/i', $html)) {
                return $this->fallbackWebsite($base, 'Magento (HTML)', 'medium');
            }

            return $this->fallbackWebsite($base, 'Sitio web estático', 'low');

        } catch (RequestException $e) {
            return [
                'platform' => 'Desconocido',
                'source_type' => 'website',
                'confidence' => 'none',
                'label' => 'No se pudo acceder',
                'description' => 'No se pudo conectar con la URL: '.$e->getMessage(),
                'config' => ['urls' => [['url' => $url]]],
                'stats' => [],
            ];
        } catch (\Exception $e) {
            Log::error('SourceDetectionService.detectFromHtml() exception', ['url' => $url, 'error' => $e->getMessage()]);

            return $this->fallbackWebsite($base, 'Sitio web estático', 'low');
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function fallbackWebsite(string $base, string $platform, string $confidence): array
    {
        return [
            'platform' => $platform,
            'source_type' => 'website',
            'confidence' => $confidence,
            'label' => "{$platform} — Web scraping",
            'description' => "Sitio detectado como {$platform}. Se usará scraping HTML con IA.",
            'config' => [
                'urls' => [['url' => $base]],
                'timeout' => 30,
                'rate_limit' => 5,
                'user_agent' => self::USER_AGENT,
            ],
            'stats' => [],
        ];
    }

    private function countShopifyProducts(Client $client, string $base): int
    {
        $total = 0;
        $page = 1;

        try {
            do {
                $r = $client->get($base.'/collections/all/products.json?limit=250&page='.$page);
                $data = json_decode((string) $r->getBody(), true);
                $count = count($data['products'] ?? []);
                $total += $count;
                $page++;
            } while ($count >= 250);
        } catch (\Exception) {
            // Return what we have
        }

        return $total;
    }

    private function normaliseUrl(string $url): string
    {
        if (! str_starts_with($url, 'http')) {
            $url = 'https://'.$url;
        }

        return rtrim($url, '/');
    }

    private function extractBaseUrl(string $url): string
    {
        $parts = parse_url($url);

        return ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');
    }

    /**
     * @deprecated Use {@see ValidatesPublicUrl::assertUrlIsPublic()} instead.
     *
     * @throws \InvalidArgumentException when the URL targets a non-public address.
     */
    private function assertPublicUrl(string $url): void
    {
        $this->assertUrlIsPublic($url);
    }
}
