<?php

namespace Modules\Supplier\Services\Extraction\Drivers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Extraction\ExtractionBatch;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Services\DocumentExtractionService;
use Modules\Supplier\Services\Extraction\Contracts\SourceDriverInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Web Scraping Driver
 *
 * Fetches HTML from supplier URLs and extracts product data using either
 * CSS selectors (manual mode) or AI vision (ai mode).
 *
 * Note: Only captures static HTML. JavaScript-rendered pages will need a
 * headless browser solution in the future.
 */
class WebScrapingDriver implements SourceDriverInterface
{
    private const USER_AGENT = 'Mozilla/5.0 (compatible; SupplierBot/1.0; +https://alsernet.es/bot)';

    public function __construct(
        private readonly DocumentExtractionService $documentService
    ) {}

    public function supports(string $sourceType): bool
    {
        return $sourceType === Source::SOURCE_TYPE_WEBSITE;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function extract(Source $source, ExtractionBatch $batch): array
    {
        $urls = $this->getUrlsFromSource($source);

        if (empty($urls)) {
            Log::info('WebScrapingDriver: no URLs configured', ['source_id' => $source->id]);

            return [];
        }

        $client = new Client([
            'timeout' => config('supplier.sources.default_timeout', 30),
            'verify' => config('supplier.sources.verify_ssl', true),
            'headers' => ['User-Agent' => self::USER_AGENT],
        ]);

        $products = [];

        foreach ($urls as $url) {
            try {
                $html = $this->fetchUrl($client, $url);

                if ($html === null) {
                    continue;
                }

                $extracted = $this->extractFromHtml($source, $html, $url);

                foreach ($extracted as &$product) {
                    $product['source_url'] = $url;
                    $product['raw_html'] = $this->cleanHtmlForStorage($html);
                }
                unset($product);

                $products = array_merge($products, $extracted);

                Log::info('WebScrapingDriver: extracted from URL', [
                    'source_id' => $source->id,
                    'url' => $url,
                    'count' => count($extracted),
                ]);

            } catch (\Exception $e) {
                Log::error('WebScrapingDriver: failed to process URL', [
                    'source_id' => $source->id,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $products;
    }

    /**
     * Fetch HTML from a URL, returning null on failure or thin content.
     */
    private function fetchUrl(Client $client, string $url): ?string
    {
        try {
            $response = $client->get($url);
            $html = (string) $response->getBody();

            if (mb_strlen($html) < 500) {
                Log::warning('WebScrapingDriver: response too short', ['url' => $url, 'length' => mb_strlen($html)]);

                return null;
            }

            return $html;

        } catch (RequestException $e) {
            Log::error('WebScrapingDriver: HTTP request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Extract products from HTML based on source extraction mode.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractFromHtml(Source $source, string $html, string $url): array
    {
        $mode = $source->extraction_mode ?? Source::EXTRACTION_MODE_AI;

        if ($mode === Source::EXTRACTION_MODE_MANUAL) {
            return $this->extractWithSelectors($source, $html);
        }

        return $this->documentService->extractProductsFromHtml($html, $url);
    }

    /**
     * Extract using CSS selectors from source configuration.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractWithSelectors(Source $source, string $html): array
    {
        $config = $source->configurations()
            ->where('config_type', 'connection')
            ->where('is_enabled', true)
            ->first();

        $selectors = $config?->config_data['selectors'] ?? [];

        if (empty($selectors)) {
            Log::warning('WebScrapingDriver: manual mode but no selectors configured', ['source_id' => $source->id]);

            return [];
        }

        $crawler = new Crawler($html);
        $products = [];

        foreach ($selectors as $productSelector) {
            try {
                $crawler->filter($productSelector)->each(function (Crawler $node) use (&$products) {
                    $products[] = [
                        'reference' => null,
                        'ean' => null,
                        'name' => trim($node->text('', false)),
                        'description' => null,
                        'price' => null,
                        'currency' => null,
                        'images' => [],
                        'attributes' => [],
                        'category' => null,
                    ];
                });
            } catch (\Exception $e) {
                Log::warning('WebScrapingDriver: selector failed', [
                    'selector' => $productSelector,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $products;
    }

    /**
     * Get configured URLs from the source configuration.
     *
     * @return array<int, string>
     */
    private function getUrlsFromSource(Source $source): array
    {
        $config = $source->configurations()
            ->where('config_type', 'connection')
            ->where('is_enabled', true)
            ->first();

        if (! $config) {
            return [];
        }

        $data = $config->config_data ?? [];

        if (isset($data['urls']) && is_array($data['urls'])) {
            return array_values(array_filter(array_map(
                fn ($u) => is_array($u) ? ($u['url'] ?? '') : (string) $u,
                $data['urls']
            )));
        }

        if (isset($data['url']) && is_string($data['url'])) {
            return [$data['url']];
        }

        return [];
    }

    /**
     * Clean and truncate HTML for storage in extraction results.
     */
    private function cleanHtmlForStorage(string $html): string
    {
        $clean = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $clean = preg_replace('/<style\b[^>]*>.*?<\/style>/is', $clean, $clean ?? '');

        return mb_substr($clean ?? '', 0, 10000);
    }
}
