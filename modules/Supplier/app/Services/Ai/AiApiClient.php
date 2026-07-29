<?php

namespace Modules\Supplier\Services\Ai;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AI API Client
 *
 * Encapsulates all outbound HTTP calls to AI providers (OpenAI, Anthropic),
 * including the OpenAI Responses API used for web-search-enabled prompts.
 * Returns a normalised response shape with content, token usage, cost and
 * latency metadata regardless of provider.
 */
class AiApiClient
{
    /**
     * Default cost per web_search_preview invocation (USD).
     * OpenAI charges per search call; price varies by tier but $0.025 covers
     * the gpt-4o-mini/gpt-4o web_search_preview tool today.
     */
    public const DEFAULT_WEB_SEARCH_COST = 0.025;

    /**
     * AI Model configurations with pricing per 1M tokens.
     */
    public const MODEL_CONFIG = [
        'gpt-4o' => [
            'provider' => 'openai',
            'input_cost_per_1m' => 2.50,
            'output_cost_per_1m' => 10.00,
            'web_search_cost_per_call' => 0.025,
            'max_tokens' => 4096,
        ],
        'gpt-4o-mini' => [
            'provider' => 'openai',
            'input_cost_per_1m' => 0.150,
            'output_cost_per_1m' => 0.600,
            'web_search_cost_per_call' => 0.025,
            'max_tokens' => 16384,
        ],
        'gpt-4o-search-preview' => [
            'provider' => 'openai',
            'input_cost_per_1m' => 2.50,
            'output_cost_per_1m' => 10.00,
            'web_search_cost_per_call' => 0.025,
            'max_tokens' => 4096,
        ],
        'claude-3-5-sonnet' => [
            'provider' => 'anthropic',
            'input_cost_per_1m' => 3.00,
            'output_cost_per_1m' => 15.00,
            'web_search_cost_per_call' => 0,
            'max_tokens' => 8192,
        ],
        'claude-3-5-haiku' => [
            'provider' => 'anthropic',
            'input_cost_per_1m' => 0.80,
            'output_cost_per_1m' => 4.00,
            'web_search_cost_per_call' => 0,
            'max_tokens' => 8192,
        ],
        // Google Gemini models — Google Search grounding incluido sin coste extra
        'gemini-2.0-flash-lite' => [
            'provider' => 'google',
            'input_cost_per_1m' => 0.0375,
            'output_cost_per_1m' => 0.15,
            'web_search_cost_per_call' => 0,
            'max_tokens' => 8192,
        ],
        'gemini-2.0-flash' => [
            'provider' => 'google',
            'input_cost_per_1m' => 0.075,
            'output_cost_per_1m' => 0.30,
            'web_search_cost_per_call' => 0,
            'max_tokens' => 8192,
        ],
        'gemini-2.5-flash' => [
            'provider' => 'google',
            'input_cost_per_1m' => 0.15,
            'output_cost_per_1m' => 0.60,
            'web_search_cost_per_call' => 0,
            'max_tokens' => 16384,
        ],
        'gemini-2.5-pro' => [
            'provider' => 'google',
            'input_cost_per_1m' => 1.25,
            'output_cost_per_1m' => 10.00,
            'web_search_cost_per_call' => 0,
            'max_tokens' => 16384,
        ],
    ];

    /**
     * Resolve OpenAI API key lazily so singleton/worker lifecycles pick up
     * key rotations without restarting. Setting::get() already caches with
     * 10-min TTL and invalidates on write.
     */
    protected function openaiApiKey(): string
    {
        return self::decryptApiKey(\Modules\Core\Models\Setting::get('supplier.openai_api_key', ''))
            ?: (string) config('services.openai.api_key');
    }

    protected function anthropicApiKey(): string
    {
        return self::decryptApiKey(\Modules\Core\Models\Setting::get('supplier.anthropic_api_key', ''))
            ?: (string) config('services.anthropic.api_key');
    }

    protected function googleApiKey(): string
    {
        return self::decryptApiKey(\Modules\Core\Models\Setting::get('supplier.google_api_key', ''))
            ?: (string) config('services.google.api_key');
    }

    private static function decryptApiKey(?string $value): string
    {
        if (! $value) {
            return '';
        }
        try {
            return decrypt($value);
        } catch (\Exception $e) {
            Log::warning('AI API key decryption failed; key may be stored in plain text', [
                'error' => $e->getMessage(),
            ]);

            return $value;
        }
    }

    /**
     * Call an AI API (OpenAI or Anthropic) to generate content.
     *
     * @param  string  $prompt  Rendered prompt text
     * @param  array  $options  API call options (model, max_tokens, temperature, enable_web_search)
     * @return array{content: string, model: string, tokens: array{input: int, output: int, total: int}, cost: float, input_cost: float, output_cost: float, web_search_cost: float, web_search_calls: int, latency_ms: int, request_id: string}
     *
     * @throws Exception When the API call fails
     */
    public function generate(string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? 'gpt-4o-mini';
        $maxTokens = $options['max_tokens'] ?? 4000;
        $temperature = $options['temperature'] ?? 0.7;
        $enableWebSearch = $options['enable_web_search'] ?? false;

        // gemini-2.0-flash y gemini-2.0-flash-lite fueron retirados por Google
        $model = match ($model) {
            'gemini-2.0-flash', 'gemini-2.0-flash-lite' => 'gemini-2.5-flash',
            default => $model,
        };

        if (! isset(self::MODEL_CONFIG[$model])) {
            throw new Exception("Unsupported AI model: {$model}");
        }

        $config = self::MODEL_CONFIG[$model];
        $provider = $config['provider'];

        // Block generation if any active budget for this provider is exceeded
        // and configured to block. Prevents runaway spend after alert threshold.
        $blockingBudget = \Modules\Supplier\Models\Ai\AiBudget::shouldBlock($provider);
        if ($blockingBudget) {
            $isDaily = $blockingBudget->isDailyExceeded();
            $window = $isDaily ? 'daily' : 'monthly';
            $usage = (float) ($isDaily ? $blockingBudget->currentDayUsage() : $blockingBudget->currentMonthUsage());
            $limit = (float) ($isDaily ? $blockingBudget->daily_limit : $blockingBudget->monthly_limit);

            Log::warning('AI generation blocked by budget', [
                'provider' => $provider,
                'model' => $model,
                'budget_id' => $blockingBudget->id,
                'window' => $window,
                'usage' => $usage,
                'limit' => $limit,
                'usage_pct' => $limit > 0 ? round($usage / $limit * 100, 2) : 0,
            ]);

            throw new \Modules\Supplier\Exceptions\BudgetExceededException(
                provider: $provider,
                window: $window,
                usage: $usage,
                limit: $limit,
            );
        }

        $startTime = microtime(true);

        // HTTP status codes that are safe to retry (transient server errors).
        // 401/403 (auth) and 429 (rate limit) must NOT be retried automatically.
        $retryableStatuses = [500, 502, 503, 504];
        $maxAttempts = 3;
        $backoffSeconds = [1, 3, 9];

        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = match ($provider) {
                    // OpenAI: siempre con web search (Responses API)
                    'openai'    => $this->callOpenAiWithSearch($prompt, $model, $maxTokens),
                    // Anthropic: web search con herramienta nativa web_search_20250305
                    'anthropic' => $this->callAnthropic($prompt, $model, $maxTokens, $temperature),
                    // Google: siempre con Google Search grounding (sin coste extra)
                    'google'    => $this->callGeminiWithSearch($prompt, $model, $maxTokens, $temperature),
                    default => throw new Exception("Unknown provider: {$provider}"),
                };

                $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

                $inputTokens = $response['usage']['prompt_tokens'];
                $outputTokens = $response['usage']['completion_tokens'];

                $inputCost = ($inputTokens / 1_000_000) * $config['input_cost_per_1m'];
                $outputCost = ($outputTokens / 1_000_000) * $config['output_cost_per_1m'];

                // OpenAI bills web_search_preview per tool invocation. The Responses
                // API does not return the call count, so when the tool is enabled we
                // assume exactly one search per request (the typical case).
                // Google: gratis. Anthropic: sin búsqueda web. OpenAI: siempre 1 llamada
                $webSearchCalls = match ($provider) {
                    'google'    => 0,
                    'anthropic' => 0,
                    'openai'    => $response['web_search_calls'] ?? 1,
                    default     => 0,
                };
                $webSearchCost = $webSearchCalls * ($config['web_search_cost_per_call'] ?? self::DEFAULT_WEB_SEARCH_COST);

                return [
                    'content'          => $response['content'],
                    'sources_used'     => $response['sources_used'] ?? [],
                    'web_search_query' => $response['web_search_query'] ?? null,
                    'model'            => $model,
                    'tokens'        => [
                        'input'  => $inputTokens,
                        'output' => $outputTokens,
                        'total'  => $inputTokens + $outputTokens,
                    ],
                    'cost'            => $inputCost + $outputCost + $webSearchCost,
                    'input_cost'      => $inputCost,
                    'output_cost'     => $outputCost,
                    'web_search_cost' => $webSearchCost,
                    'web_search_calls' => $webSearchCalls,
                    'latency_ms'      => $latencyMs,
                    'request_id'      => $response['request_id'] ?? Str::uuid()->toString(),
                ];
            } catch (ConnectionException $e) {
                // Network-level failure (timeout, DNS, refused connection) — always retryable.
                $lastException = $e;

                if ($attempt < $maxAttempts) {
                    Log::warning('AI API connection error, retrying', [
                        'attempt'  => $attempt,
                        'model'    => $model,
                        'provider' => $provider,
                        'error'    => $e->getMessage(),
                        'wait_s'   => $backoffSeconds[$attempt - 1],
                    ]);
                    sleep($backoffSeconds[$attempt - 1]);

                    continue;
                }
            } catch (Exception $e) {
                // Determine whether this is a retryable HTTP status error.
                $isRetryable = false;
                foreach ($retryableStatuses as $status) {
                    if (str_contains($e->getMessage(), (string) $status)) {
                        $isRetryable = true;
                        break;
                    }
                }

                if (! $isRetryable) {
                    // Non-retryable error (auth failure, bad request, rate limit, etc.).
                    Log::error('AI API call failed (non-retryable)', [
                        'model'    => $model,
                        'provider' => $provider,
                        'error'    => $e->getMessage(),
                    ]);

                    throw new Exception("AI API call failed: {$e->getMessage()}");
                }

                $lastException = $e;

                if ($attempt < $maxAttempts) {
                    Log::warning('AI API transient error, retrying', [
                        'attempt'  => $attempt,
                        'model'    => $model,
                        'provider' => $provider,
                        'error'    => $e->getMessage(),
                        'wait_s'   => $backoffSeconds[$attempt - 1],
                    ]);
                    sleep($backoffSeconds[$attempt - 1]);

                    continue;
                }
            }
        }

        // All attempts exhausted — log and re-throw the original exception.
        Log::error('AI API call failed after retries', [
            'model'    => $model,
            'provider' => $provider,
            'attempts' => $maxAttempts,
            'error'    => $lastException?->getMessage(),
        ]);

        throw new Exception("AI API call failed: {$lastException?->getMessage()}");
    }

    /**
     * Call the OpenAI Chat Completions API.
     *
     * @return array{content: string, usage: array{prompt_tokens: int, completion_tokens: int}, request_id: ?string}
     *
     * @throws Exception When the API call fails
     */
    protected function callOpenAi(string $prompt, string $model, int $maxTokens, float $temperature): array
    {
        if (! $this->openaiApiKey()) {
            throw new Exception('OpenAI API key not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->openaiApiKey()}",
            'Content-Type' => 'application/json',
        ])
            ->timeout(120)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional product content writer. Generate high-quality, SEO-optimized product descriptions.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

        if (! $response->successful()) {
            throw new Exception("OpenAI API error: {$response->status()} - {$response->body()}");
        }

        $data = $response->json();

        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'usage' => [
                'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
            ],
            'request_id' => $response->header('x-request-id'),
        ];
    }

    /**
     * Call the OpenAI Responses API with the web_search_preview tool.
     *
     * @return array{content: string, usage: array{prompt_tokens: int, completion_tokens: int}, request_id: ?string}
     *
     * @throws Exception When the API call fails
     */
    protected function callOpenAiWithSearch(string $prompt, string $model, int $maxTokens): array
    {
        if (! $this->openaiApiKey()) {
            throw new Exception('OpenAI API key not configured');
        }

        $searchBackoff = [1, 3, 9];
        $searchAttempts = 3;
        $lastSearchException = null;

        for ($attempt = 1; $attempt <= $searchAttempts; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$this->openaiApiKey()}",
                    'Content-Type' => 'application/json',
                ])
                    ->timeout(120)
                    ->post('https://api.openai.com/v1/responses', [
                        'model' => $model,
                        'max_output_tokens' => $maxTokens,
                        'tools' => [['type' => 'web_search_preview']],
                        'tool_choice' => 'required',
                        'input' => [
                            [
                                'role' => 'system',
                                'content' => implode("\n", [
                                    'Eres un experto en SEO y copywriter especializado en e-commerce.',
                                    'PROCESO OBLIGATORIO — sigue estos pasos en orden:',
                                    '  PASO 1 — BÚSQUEDA: Busca el producto en internet usando esta prioridad:',
                                    '    a) EAN-13 del artículo (campo ean13 en product_attributes) — resultado exacto.',
                                    '    b) Código del artículo (campo code en product_attributes).',
                                    '    c) Nombre comercial del modelo + marca.',
                                    '  PASO 2 — LEE los resultados y extrae información real del producto.',
                                    '  PASO 3 — REDACTA el contenido basándote en lo encontrado en la web.',
                                    '  PASO 4 — Al terminar, añade al final del texto un bloque exactamente así:',
                                    'SOURCES:',
                                    'https://url-real-1.com',
                                    'https://url-real-2.com',
                                    'Pon las URLs reales que consultaste. Mínimo 1, máximo 5.',
                                ]),
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt,
                            ],
                        ],
                    ]);

                break; // Request succeeded — exit retry loop.
            } catch (ConnectionException $e) {
                $lastSearchException = $e;

                if ($attempt < $searchAttempts) {
                    Log::warning('callOpenAiWithSearch connection error, retrying', [
                        'attempt' => $attempt,
                        'model'   => $model,
                        'error'   => $e->getMessage(),
                        'wait_s'  => $searchBackoff[$attempt - 1],
                    ]);
                    sleep($searchBackoff[$attempt - 1]);

                    continue;
                }

                throw $e;
            }
        }

        if (! $response->successful()) {
            throw new Exception("OpenAI Responses API error: {$response->status()} - {$response->body()}");
        }

        $data = $response->json();

        $text = '';
        $sources = [];
        $webSearchQuery = null;

        // Normaliza una URL eliminando parámetros de tracking (utm_*, ref, etc.)
        // para evitar duplicados entre la misma URL con y sin tracking.
        $normalizeUrl = static function (string $url): string {
            $parts = parse_url($url);
            if (empty($parts['host'])) {
                return $url;
            }
            $tracking = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'ref', 'fbclid', 'gclid'];
            $query = [];
            parse_str($parts['query'] ?? '', $query);
            foreach ($tracking as $k) {
                unset($query[$k]);
            }
            $qs = $query ? '?' . http_build_query($query) : '';

            return ($parts['scheme'] ?? 'https') . '://' . $parts['host'] . ($parts['path'] ?? '') . $qs;
        };

        foreach ($data['output'] ?? [] as $item) {
            if (($item['type'] ?? '') === 'web_search_call') {
                // Capturar la query real del modelo — si supera 200 chars es el prompt completo → ignorar
                if (! $webSearchQuery) {
                    $q = $item['action']['queries'][0] ?? $item['action']['query'] ?? null;
                    if ($q && mb_strlen($q) <= 200) {
                        $webSearchQuery = $q;
                    }
                }
                foreach ($item['results'] ?? [] as $result) {
                    if (! empty($result['url'])) {
                        $key = $normalizeUrl($result['url']);
                        if (! isset($sources[$key])) {
                            $sources[$key] = [
                                'url'   => $key,
                                'title' => $result['title'] ?? null,
                            ];
                        }
                    }
                }
            }

            if ($item['type'] !== 'message') {
                continue;
            }

            foreach ($item['content'] ?? [] as $part) {
                if ($part['type'] === 'output_text') {
                    $text .= $part['text'];

                    foreach ($part['annotations'] ?? [] as $annotation) {
                        if (($annotation['type'] ?? '') === 'url_citation' && ! empty($annotation['url'])) {
                            $key = $normalizeUrl($annotation['url']);
                            if (! isset($sources[$key])) {
                                $sources[$key] = [
                                    'url'   => $key,
                                    'title' => $annotation['title'] ?? null,
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Extraer bloque SOURCES: del texto y limpiarlo antes de devolver
        if (preg_match('/\bSOURCES:\s*\n((?:https?:\/\/\S+\s*\n?)+)/i', $text, $sourceMatch)) {
            $text = trim(preg_replace('/\bSOURCES:\s*\n(?:https?:\/\/\S+\s*\n?)*/i', '', $text));
            foreach (preg_split('/\s+/', trim($sourceMatch[1])) as $url) {
                $url = trim($url);
                if ($url && ! isset($sources[$url])) {
                    $sources[$url] = ['url' => $url, 'title' => null];
                }
            }
        }

        return [
            'content'           => $text,
            'sources_used'      => array_values($sources),
            'web_search_query'  => $webSearchQuery,
            'usage'             => [
                'prompt_tokens'     => $data['usage']['input_tokens'] ?? 0,
                'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
            ],
            'request_id' => $data['id'] ?? null,
        ];
    }

    /**
     * Call the Anthropic (Claude) Messages API.
     *
     * @return array{content: string, usage: array{prompt_tokens: int, completion_tokens: int}, request_id: ?string}
     *
     * @throws Exception When the API call fails
     */
    protected function callAnthropic(string $prompt, string $model, int $maxTokens, float $temperature): array
    {
        if (! $this->anthropicApiKey()) {
            throw new Exception('Anthropic API key not configured');
        }

        $response = Http::withHeaders([
            'x-api-key'         => $this->anthropicApiKey(),
            'anthropic-version' => '2023-06-01',
            'anthropic-beta'    => 'web-search-2025-03-05',
            'Content-Type'      => 'application/json',
        ])
            ->timeout(120)
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => $model,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'tools' => [[
                    'type'     => 'web_search_20250305',
                    'name'     => 'web_search',
                    'max_uses' => 5,
                ]],
                'system' => implode("\n", [
                    'Eres un experto en SEO y copywriter especializado en e-commerce.',
                    'OBLIGATORIO: Antes de redactar busca el producto en internet por EAN, código de artículo o nombre comercial.',
                    'Basa el contenido exclusivamente en lo que encuentres en la web.',
                ]),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (! $response->successful()) {
            throw new Exception("Anthropic API error: {$response->status()} - {$response->body()}");
        }

        $data    = $response->json();
        $sources = [];
        $webSearchQuery = null;
        $text    = '';

        foreach ($data['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'];
            }
            // Extraer fuentes de los resultados de web_search
            if (($block['type'] ?? '') === 'tool_result') {
                foreach ($block['content'] ?? [] as $result) {
                    if (($result['type'] ?? '') === 'web_search_result' && ! empty($result['url'])) {
                        $url = $result['url'];
                        if (! isset($sources[$url])) {
                            $sources[$url] = ['url' => $url, 'title' => $result['title'] ?? null];
                        }
                    }
                }
            }
            // Query usada por Claude para buscar
            if (($block['type'] ?? '') === 'tool_use' && ($block['name'] ?? '') === 'web_search') {
                $webSearchQuery ??= $block['input']['query'] ?? null;
            }
        }

        return [
            'content'          => $text,
            'sources_used'     => array_values($sources),
            'web_search_query' => $webSearchQuery,
            'usage'            => [
                'prompt_tokens'     => $data['usage']['input_tokens']  ?? 0,
                'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
            ],
            'request_id' => $response->header('request-id'),
        ];
    }

    /**
     * Call Google Gemini API (generateContent).
     *
     * @return array{content: string, sources_used: array, web_search_query: ?string, usage: array, request_id: ?string}
     */
    /**
     * Construye el generationConfig para Gemini.
     *
     * NO se fija maxOutputTokens: los modelos gemini-2.5-* usan "thinking"
     * (razonamiento interno) que consume parte de ese presupuesto, y al limitarlo
     * el razonamiento se comía casi todo y la descripción salía CORTADA (p.ej. se
     * detenía en mitad de las características). Sin maxOutputTokens, Gemini usa el
     * máximo del modelo y deja espacio de sobra para la respuesta completa.
     * Además acotamos el thinking para que el grueso del output sea texto visible.
     */
    protected function geminiGenerationConfig(string $model, int $maxTokens, float $temperature): array
    {
        $config = [
            'temperature' => $temperature,
        ];

        // Solo los modelos 2.5 soportan thinkingConfig. Acotamos el presupuesto de
        // razonamiento para que no consuma el espacio de la descripción.
        if (str_contains($model, '2.5')) {
            $config['thinkingConfig'] = ['thinkingBudget' => 2048];
        }

        return $config;
    }

    protected function callGemini(string $prompt, string $model, int $maxTokens, float $temperature): array
    {
        if (! $this->googleApiKey()) {
            throw new Exception('Google API key not configured');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->googleApiKey()}";

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(120)
            ->post($url, [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $prompt]]],
                ],
                'systemInstruction' => [
                    'parts' => [['text' => 'You are a professional product content writer. Generate high-quality, SEO-optimized product descriptions in Spanish.']],
                ],
                'generationConfig' => $this->geminiGenerationConfig($model, $maxTokens, $temperature),
            ]);

        if (! $response->successful()) {
            throw new Exception("Google Gemini API error: {$response->status()} - {$response->body()}");
        }

        return $this->parseGeminiResponse($response->json());
    }

    /**
     * Call Google Gemini API with Google Search grounding.
     * The grounding metadata always returns the URLs consulted — more reliable than OpenAI citations.
     *
     * @return array{content: string, sources_used: array, web_search_query: ?string, usage: array, request_id: ?string}
     */
    protected function callGeminiWithSearch(string $prompt, string $model, int $maxTokens, float $temperature): array
    {
        if (! $this->googleApiKey()) {
            throw new Exception('Google API key not configured');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->googleApiKey()}";

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(120)
            ->post($url, [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $prompt]]],
                ],
                'systemInstruction' => [
                    'parts' => [['text' => implode("\n", [
                        'Eres un experto en SEO y copywriter especializado en e-commerce.',
                        'PROCESO OBLIGATORIO — sigue estos pasos en orden:',
                        '  PASO 1 — BÚSQUEDA: Busca el producto en internet usando esta prioridad:',
                        '    a) EAN-13 del artículo (campo ean13 en product_attributes) — resultado exacto.',
                        '    b) Código del artículo (campo code en product_attributes).',
                        '    c) Nombre comercial del modelo + marca.',
                        '  PASO 2 — LEE los resultados y extrae información real del producto.',
                        '  PASO 3 — REDACTA el contenido basándote en lo encontrado en la web.',
                        '  PASO 4 — Al terminar, añade al final del texto un bloque exactamente así:',
                        'SOURCES:',
                        'https://url-real-1.com',
                        'https://url-real-2.com',
                        'Pon las URLs reales que consultaste. Mínimo 1, máximo 5.',
                    ])]],
                ],
                'tools' => [['google_search' => new \stdClass]],
                'generationConfig' => $this->geminiGenerationConfig($model, $maxTokens, $temperature),
            ]);

        if (! $response->successful()) {
            throw new Exception("Google Gemini API error: {$response->status()} - {$response->body()}");
        }

        return $this->parseGeminiResponse($response->json());
    }

    /**
     * Parse a Gemini generateContent response into the normalised shape.
     */
    private function parseGeminiResponse(array $data): array
    {
        $candidate = $data['candidates'][0] ?? [];
        $text = '';
        foreach ($candidate['content']['parts'] ?? [] as $part) {
            $text .= $part['text'] ?? '';
        }

        // Extract sources from Google Search grounding metadata
        $sources = [];
        $webSearchQuery = null;
        $grounding = $candidate['groundingMetadata'] ?? [];

        if (! empty($grounding['webSearchQueries'])) {
            $webSearchQuery = $grounding['webSearchQueries'][0];
        }

        foreach ($grounding['groundingChunks'] ?? [] as $chunk) {
            $uri   = $chunk['web']['uri']   ?? null;
            $title = $chunk['web']['title'] ?? null;
            if ($uri && ! isset($sources[$uri])) {
                $sources[$uri] = ['url' => $uri, 'title' => $title];
            }
        }

        // Also extract SOURCES: block from text if present
        if (preg_match('/\bSOURCES:\s*\n((?:https?:\/\/\S+\s*\n?)+)/i', $text, $m)) {
            $text = trim(preg_replace('/\bSOURCES:\s*\n(?:https?:\/\/\S+\s*\n?)*/i', '', $text));
            foreach (preg_split('/\s+/', trim($m[1])) as $url) {
                $url = trim($url);
                if ($url && ! isset($sources[$url])) {
                    $sources[$url] = ['url' => $url, 'title' => null];
                }
            }
        }

        $usage = $data['usageMetadata'] ?? [];

        return [
            'content'          => $text,
            'sources_used'     => array_values($sources),
            'web_search_query' => $webSearchQuery,
            'usage'            => [
                'prompt_tokens'     => $usage['promptTokenCount']     ?? 0,
                'completion_tokens' => $usage['candidatesTokenCount'] ?? 0,
            ],
            'request_id' => null,
        ];
    }
}
