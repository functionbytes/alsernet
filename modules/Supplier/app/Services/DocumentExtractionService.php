<?php

namespace Modules\Supplier\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Ai\AiCost;

/**
 * Document Extraction Service
 *
 * Uses OpenAI GPT-4o to extract structured product data from documents
 * and web pages. This is an extraction service, not a content generation
 * service — it reads raw supplier data and normalises it.
 */
class DocumentExtractionService
{
    /**
     * Cost per 1M tokens by model.
     */
    private const MODEL_COSTS = [
        'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
        'gpt-4o-mini' => ['input' => 0.150, 'output' => 0.600],
    ];

    private const EXTRACTION_MODEL = 'gpt-4o-mini';

    private const VISION_MODEL = 'gpt-4o';

    private const MAX_TEXT_LENGTH = 15000;

    protected ?string $openaiApiKey;

    public function __construct()
    {
        $this->openaiApiKey = self::decryptApiKey(\Modules\Core\Models\Setting::get('supplier.openai_api_key')) ?: config('services.openai.api_key');
    }

    private static function decryptApiKey(?string $value): string
    {
        if (! $value) {
            return '';
        }
        try {
            return decrypt($value);
        } catch (\Exception) {
            return $value;
        }
    }

    /**
     * Extract products from cleaned HTML of a supplier web page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function extractProductsFromHtml(string $html, string $sourceUrl = ''): array
    {
        $cleaned = $this->cleanHtml($html);
        $truncated = mb_substr($cleaned, 0, self::MAX_TEXT_LENGTH);

        $prompt = <<<PROMPT
        Eres un extractor de datos de productos. Del siguiente HTML de una página web de proveedor, extrae TODOS los productos con su información.

        Para cada producto devuelve un JSON con estos campos (null si no disponible):
        - reference: código/referencia del producto
        - ean: código EAN/barcode
        - name: nombre del producto
        - description: descripción
        - price: precio como número flotante
        - currency: moneda (EUR, USD, etc)
        - images: array de URLs de imágenes
        - attributes: objeto con atributos adicionales (talla, color, peso, etc)
        - category: categoría del producto

        Devuelve SOLO un JSON array válido. Sin explicaciones.

        URL fuente: {$sourceUrl}
        HTML:
        {$truncated}
        PROMPT;

        return $this->callExtractionApi($prompt, self::EXTRACTION_MODEL, 'extraction');
    }

    /**
     * Extract products from plain text (e.g. from a PDF or Word document).
     *
     * @return array<int, array<string, mixed>>
     */
    public function extractProductsFromText(string $text, string $docType = 'pdf'): array
    {
        $truncated = mb_substr($text, 0, self::MAX_TEXT_LENGTH);

        $prompt = <<<PROMPT
        Eres un extractor de datos de productos. Del siguiente texto extraído de un documento {$docType} de proveedor, extrae TODOS los productos.

        Para cada producto devuelve un JSON con estos campos (null si no disponible):
        - reference: código/referencia del producto
        - ean: código EAN/barcode
        - name: nombre del producto
        - description: descripción
        - price: precio como número flotante
        - currency: moneda (EUR, USD, etc)
        - images: array vacío []
        - attributes: objeto con atributos adicionales
        - category: categoría del producto

        Devuelve SOLO un JSON array válido. Sin explicaciones.

        TEXTO:
        {$truncated}
        PROMPT;

        return $this->callExtractionApi($prompt, self::EXTRACTION_MODEL, 'extraction');
    }

    /**
     * Extract products from a file using vision (for scanned PDFs and images).
     *
     * @return array<int, array<string, mixed>>
     */
    public function extractProductsFromFileVision(string $filePath, string $fileType = 'pdf'): array
    {
        if (! file_exists($filePath)) {
            Log::warning('DocumentExtractionService: file not found for vision extraction', ['path' => $filePath]);

            return [];
        }

        $maxBytes = 20 * 1024 * 1024; // 20 MB
        if (filesize($filePath) > $maxBytes) {
            Log::warning('DocumentExtractionService: file too large for vision extraction', [
                'path' => $filePath,
                'size_mb' => round(filesize($filePath) / 1024 / 1024, 2),
                'limit_mb' => 20,
            ]);

            return [];
        }

        $base64 = base64_encode(file_get_contents($filePath));
        $mimeType = $fileType === 'pdf' ? 'application/pdf' : $this->guessMimeFromType($fileType);

        $prompt = 'Eres un extractor de datos de productos. De la siguiente imagen/documento de un proveedor, extrae TODOS los productos visibles. '
            .'Para cada producto devuelve un JSON con: reference, ean, name, description, price (float), currency, images (array vacío), attributes (objeto), category. '
            .'Devuelve SOLO un JSON array válido. Sin explicaciones.';

        return $this->callVisionApi($prompt, $base64, $mimeType);
    }

    /**
     * Extract products from structured tabular data (Excel/CSV rows).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function extractProductsFromStructuredData(array $rows, string $format = 'excel'): array
    {
        if (empty($rows)) {
            return [];
        }

        $sample = array_slice($rows, 0, 200);
        $text = '';

        foreach ($sample as $row) {
            $text .= implode(' | ', array_map(fn ($v) => is_array($v) ? json_encode($v) : (string) $v, $row))."\n";
        }

        $truncated = mb_substr($text, 0, self::MAX_TEXT_LENGTH);

        $prompt = <<<PROMPT
        Eres un extractor de datos de productos. Los siguientes datos provienen de un archivo {$format} de proveedor.
        Mapea las columnas a campos de producto y devuelve un JSON array.

        Para cada fila/producto devuelve (null si no disponible):
        - reference: código/referencia del producto
        - ean: código EAN/barcode
        - name: nombre del producto
        - description: descripción
        - price: precio como número flotante
        - currency: moneda (EUR, USD, etc)
        - images: array vacío []
        - attributes: objeto con otros atributos relevantes
        - category: categoría del producto

        Devuelve SOLO un JSON array válido. Sin explicaciones.

        DATOS:
        {$truncated}
        PROMPT;

        return $this->callExtractionApi($prompt, self::EXTRACTION_MODEL, 'extraction');
    }

    /**
     * Clean HTML by stripping irrelevant tags.
     */
    private function cleanHtml(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);

        foreach (['nav', 'footer', 'header', 'aside', 'iframe'] as $tag) {
            $html = preg_replace("/<{$tag}\b[^>]*>.*?<\/{$tag}>/is", '', $html);
        }

        return strip_tags($html);
    }

    /**
     * Call OpenAI chat completion API for text-based extraction.
     *
     * @return array<int, array<string, mixed>>
     */
    private function callExtractionApi(string $prompt, string $model, string $operationType): array
    {
        if (! $this->openaiApiKey) {
            Log::warning('DocumentExtractionService: OpenAI API key not configured');

            return [];
        }

        try {
            $start = microtime(true);
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->openaiApiKey}",
                'Content-Type' => 'application/json',
            ])
                ->timeout(90)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Eres un extractor de datos de productos. Siempre devuelves JSON válido.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 4096,
                    'temperature' => 0.1,
                    'response_format' => ['type' => 'json_object'],
                ]);
            $latencyMs = (int) ((microtime(true) - $start) * 1000);

            if (! $response->successful()) {
                Log::error('DocumentExtractionService: OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '[]';
            $inputTokens = $data['usage']['prompt_tokens'] ?? 0;
            $outputTokens = $data['usage']['completion_tokens'] ?? 0;

            $this->trackCost($model, $inputTokens, $outputTokens, $operationType, $latencyMs);

            return $this->parseProductArray($content);

        } catch (\Exception $e) {
            Log::error('DocumentExtractionService: extraction failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Call OpenAI vision API for image/scanned document extraction.
     *
     * @return array<int, array<string, mixed>>
     */
    private function callVisionApi(string $prompt, string $base64Content, string $mimeType): array
    {
        if (! $this->openaiApiKey) {
            Log::warning('DocumentExtractionService: OpenAI API key not configured');

            return [];
        }

        try {
            $start = microtime(true);
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->openaiApiKey}",
                'Content-Type' => 'application/json',
            ])
                ->timeout(120)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => self::VISION_MODEL,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => $prompt],
                                [
                                    'type' => 'image_url',
                                    'image_url' => ['url' => "data:{$mimeType};base64,{$base64Content}"],
                                ],
                            ],
                        ],
                    ],
                    'max_tokens' => 4096,
                    'temperature' => 0.1,
                ]);
            $latencyMs = (int) ((microtime(true) - $start) * 1000);

            if (! $response->successful()) {
                Log::error('DocumentExtractionService: OpenAI vision API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '[]';
            $inputTokens = $data['usage']['prompt_tokens'] ?? 0;
            $outputTokens = $data['usage']['completion_tokens'] ?? 0;

            $this->trackCost(self::VISION_MODEL, $inputTokens, $outputTokens, 'extraction_vision', $latencyMs);

            return $this->parseProductArray($content);

        } catch (\Exception $e) {
            Log::error('DocumentExtractionService: vision extraction failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Parse a JSON string into a product array.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseProductArray(string $content): array
    {
        $content = trim($content);

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            preg_match('/\[.*\]/s', $content, $matches);
            if (! empty($matches[0])) {
                $decoded = json_decode($matches[0], true);
            }
        }

        if (! is_array($decoded)) {
            Log::warning('DocumentExtractionService: could not parse product JSON', ['raw' => mb_substr($content, 0, 500)]);

            return [];
        }

        // Handle {"products": [...]} wrapper returned when using json_object response format
        if (! array_is_list($decoded)) {
            foreach (['products', 'items', 'data', 'results'] as $key) {
                if (isset($decoded[$key]) && is_array($decoded[$key])) {
                    $decoded = $decoded[$key];
                    break;
                }
            }
        }

        // Single product as associative array — wrap it
        if (! array_is_list($decoded) && isset($decoded['name'])) {
            $decoded = [$decoded];
        }

        if (! array_is_list($decoded)) {
            return [];
        }

        return array_map(fn ($item) => $this->normaliseProduct($item), array_filter($decoded, 'is_array'));
    }

    /**
     * Normalise a single product array to the expected structure.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normaliseProduct(array $item): array
    {
        return [
            'reference' => $item['reference'] ?? null,
            'ean' => $item['ean'] ?? null,
            'name' => $item['name'] ?? null,
            'description' => $item['description'] ?? null,
            'price' => isset($item['price']) ? (float) $item['price'] : null,
            'currency' => $item['currency'] ?? null,
            'images' => (array) ($item['images'] ?? []),
            'attributes' => (array) ($item['attributes'] ?? []),
            'category' => $item['category'] ?? null,
        ];
    }

    /**
     * Record AI usage cost.
     */
    private function trackCost(string $model, int $inputTokens, int $outputTokens, string $operationType, ?int $latencyMs = null): void
    {
        try {
            $costs = self::MODEL_COSTS[$model] ?? ['input' => 0, 'output' => 0];

            AiCost::create([
                'model' => $model,
                'operation_type' => $operationType,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'input_cost' => ($inputTokens / 1_000_000) * $costs['input'],
                'output_cost' => ($outputTokens / 1_000_000) * $costs['output'],
                'latency_ms' => $latencyMs,
            ]);
        } catch (\Exception $e) {
            Log::warning('DocumentExtractionService: failed to track AI cost', ['error' => $e->getMessage()]);
        }
    }

    private function guessMimeFromType(string $fileType): string
    {
        return match ($fileType) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }
}
