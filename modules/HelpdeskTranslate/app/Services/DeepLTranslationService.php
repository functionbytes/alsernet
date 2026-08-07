<?php

namespace Modules\HelpdeskTranslate\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Setting;

class DeepLTranslationService
{
    /** Reintentos por fallo de conexión (no por respuestas 4xx/5xx). */
    private const RETRY_TIMES = 2;

    private const RETRY_SLEEP_MS = 200;

    /** Memoized API key for the duration of this container instance. */
    private ?string $apiKey = null;

    /** Memoized base URL for the duration of this container instance. */
    private ?string $baseUrl = null;

    /**
     * Translate text via DeepL.
     * No Cache::remember here — CachedTranslator owns the DB-level cache layer.
     */
    public function translate(string $text, string $targetLang = 'ES', ?string $sourceLang = null, ?int $timeoutSeconds = null, ?int $retries = null): ?string
    {
        return $this->translateWithSource($text, $targetLang, $sourceLang, $timeoutSeconds, $retries)['translated'];
    }

    /**
     * Como translate(), pero además devuelve `detected_source_language` de la
     * MISMA respuesta de DeepL (la API ya lo incluye en /v2/translate cuando
     * no se manda `source_lang`) — evita que el llamador tenga que hacer una
     * segunda petición aparte solo para saber en qué idioma escribió el
     * cliente (antes: TranslateItemController llamaba translate() y
     * detectLanguage() por separado, dos round-trips HTTP para una sola
     * acción de agente).
     *
     * @return array{translated: ?string, detected_source_language: ?string}
     */
    public function translateWithSource(string $text, string $targetLang = 'ES', ?string $sourceLang = null, ?int $timeoutSeconds = null, ?int $retries = null): array
    {
        if (empty(trim($text))) {
            return ['translated' => null, 'detected_source_language' => null];
        }

        $apiKey = $this->resolveApiKey();

        if (empty($apiKey)) {
            return ['translated' => null, 'detected_source_language' => null];
        }

        return $this->callDeepL($text, strtoupper($targetLang), $sourceLang ? strtoupper($sourceLang) : null, $apiKey, $timeoutSeconds, $retries);
    }

    /**
     * Translate + return the detected source language. Used by detection
     * flows where we want to know what language the visitor wrote in.
     * Cached to avoid burning DeepL quota on repeated detection snippets.
     *
     * @return array{translated: ?string, detected_source_language: ?string}
     */
    public function translateWithDetection(string $text, string $targetLang = 'ES', ?int $timeoutSeconds = null, ?int $retries = null): array
    {
        $apiKey = $this->resolveApiKey();
        if (empty($apiKey) || trim($text) === '') {
            return ['translated' => null, 'detected_source_language' => null];
        }

        $cacheKey = 'helpdesk:ai:detect:'.md5($text.$targetLang);

        $result = Cache::remember($cacheKey, now()->addHours(24), function () use ($text, $targetLang, $apiKey, $timeoutSeconds, $retries) {
            try {
                $response = Http::withHeaders(['Authorization' => "DeepL-Auth-Key {$apiKey}"])
                    ->timeout($timeoutSeconds ?? 15)
                    ->retry($retries ?? self::RETRY_TIMES, self::RETRY_SLEEP_MS, fn (\Throwable $e) => $e instanceof ConnectionException, false)
                    ->post("{$this->resolveBaseUrl()}/v2/translate", [
                        'text' => [$text],
                        'target_lang' => strtoupper($targetLang),
                    ]);

                if ($response->failed()) {
                    return null;
                }

                $detected = $response->json('translations.0.detected_source_language');

                return [
                    'translated' => $response->json('translations.0.text'),
                    'detected_source_language' => $detected ? strtolower($detected) : null,
                ];
            } catch (\Throwable) {
                return null;
            }
        });

        // Do not keep null in cache — next call might succeed.
        if ($result === null) {
            Cache::forget($cacheKey);

            return ['translated' => null, 'detected_source_language' => null];
        }

        return $result;
    }

    private function resolveApiKey(): ?string
    {
        if ($this->apiKey !== null) {
            return $this->apiKey === '' ? null : $this->apiKey;
        }

        $this->apiKey = Setting::get('helpdesktranslate.deepl.key')
            ?: config('helpdesktranslate.deepl.key')
            ?: config('services.deepl.key')
            ?: '';

        return $this->apiKey === '' ? null : $this->apiKey;
    }

    private function resolveBaseUrl(): string
    {
        if ($this->baseUrl !== null) {
            return $this->baseUrl;
        }

        $this->baseUrl = Setting::get('helpdesktranslate.deepl.url')
            ?: config('helpdesktranslate.deepl.url')
            ?: config('services.deepl.url', 'https://api-free.deepl.com');

        return $this->baseUrl;
    }

    /**
     * @param  ?int  $timeoutSeconds  Override del timeout por defecto (15s) — usado
     *                                por los flujos automáticos (auto_incoming/
     *                                auto_outgoing) para no bloquear un job de
     *                                cola con el timeout completo de una traducción
     *                                interactiva; un fallo aquí siempre degrada al
     *                                texto original, nunca bloquea el envío.
     * @param  ?int  $retries  Override de reintentos por fallo de conexión (2 por
     *                         defecto) — 0 en los flujos automáticos: cada intento
     *                         adicional multiplica el peor caso por su timeout.
     * @return array{translated: ?string, detected_source_language: ?string}
     */
    private function callDeepL(string $text, string $targetLang, ?string $sourceLang, string $apiKey, ?int $timeoutSeconds = null, ?int $retries = null): array
    {
        $baseUrl = $this->resolveBaseUrl();

        $payload = [
            'text' => [$text],
            'target_lang' => $targetLang,
        ];

        if ($sourceLang) {
            $payload['source_lang'] = $sourceLang;
        }

        try {
            $response = Http::withHeaders(['Authorization' => "DeepL-Auth-Key {$apiKey}"])
                ->timeout($timeoutSeconds ?? 15)
                ->retry($retries ?? self::RETRY_TIMES, self::RETRY_SLEEP_MS, fn (\Throwable $e) => $e instanceof ConnectionException, false)
                ->post("{$baseUrl}/v2/translate", $payload);

            if ($response->failed()) {
                Log::warning('DeepLTranslationService: translation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['translated' => null, 'detected_source_language' => null];
            }

            $detected = $response->json('translations.0.detected_source_language');

            return [
                'translated' => $response->json('translations.0.text'),
                'detected_source_language' => $detected ? strtolower($detected) : null,
            ];
        } catch (\Throwable $e) {
            Log::error('DeepLTranslationService: exception', ['error' => $e->getMessage()]);

            return ['translated' => null, 'detected_source_language' => null];
        }
    }
}
