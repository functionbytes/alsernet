<?php

namespace Modules\HelpdeskTranslate\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Setting;

class TranslationService
{
    /** Reintentos por fallo de conexión (no por respuestas 4xx/5xx). */
    private const RETRY_TIMES = 2;

    private const RETRY_SLEEP_MS = 200;

    private string $endpoint;

    private string $apiKey;

    public function __construct()
    {
        // Settings (admin panel) take precedence over module config and the
        // legacy helpdesk.translation.* keys so existing setups keep working.
        // We use `?:` (not `??`) on purpose — operators "clear" a setting by
        // saving the empty string, and `??` would skip the config fallback.
        $this->endpoint = (string) (Setting::get('helpdesktranslate.libretranslate.endpoint')
            ?: config('helpdesktranslate.libretranslate.endpoint')
            ?: config('helpdesk.translation.endpoint', ''));
        $this->apiKey = (string) (Setting::get('helpdesktranslate.libretranslate.api_key')
            ?: config('helpdesktranslate.libretranslate.api_key')
            ?: config('helpdesk.translation.api_key', ''));
    }

    /**
     * Best-effort language detection via LibreTranslate's /detect endpoint.
     * Returns an ISO 639-1 code or null when detection is disabled/unavailable.
     * Caches successful results to avoid burning quota on repeated snippets.
     */
    public function detectLanguage(string $text, ?int $timeoutSeconds = null, ?int $retries = null): ?string
    {
        if (empty($this->endpoint) || trim($text) === '') {
            return null;
        }

        try {
            $base = rtrim($this->endpoint, '/');
            // Endpoint config might point to /translate; strip it for /detect.
            $base = preg_replace('#/translate$#', '', $base);

            $cacheKey = 'helpdesk:translate:detect:'.md5($text.$base);

            $detected = Cache::remember($cacheKey, now()->addHours(24), function () use ($text, $base, $timeoutSeconds, $retries) {
                $payload = ['q' => $text];
                if ($this->apiKey !== '') {
                    $payload['api_key'] = $this->apiKey;
                }

                $resp = Http::timeout($timeoutSeconds ?? 5)
                    ->retry($retries ?? self::RETRY_TIMES, self::RETRY_SLEEP_MS, fn (\Throwable $e) => $e instanceof ConnectionException, false)
                    ->asForm()
                    ->post("{$base}/detect", $payload)
                    ->json();

                return $resp[0]['language'] ?? null;
            });

            // Do not keep null in cache — next call might succeed.
            if ($detected === null) {
                Cache::forget($cacheKey);
            }

            return $detected;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Translate text using LibreTranslate.
     * No Cache::remember here — CachedTranslator owns the DB-level cache layer.
     *
     * @return array{translated: string, detected: string, mocked?: bool, failed?: bool}
     */
    public function translate(string $text, string $from = 'auto', string $to = 'es', ?int $timeoutSeconds = null, ?int $retries = null): array
    {
        if (empty($this->endpoint)) {
            return $this->mockResponse($text, $from, $to);
        }

        return $this->callLibreTranslate($text, $from, $to, $timeoutSeconds, $retries);
    }

    /**
     * @return array{translated: string, detected: string, failed?: bool}
     */
    private function callLibreTranslate(string $text, string $from, string $to, ?int $timeoutSeconds = null, ?int $retries = null): array
    {
        $payload = [
            'q' => $text,
            'source' => $from,
            'target' => $to,
            'format' => 'text',
        ];

        if ($this->apiKey !== '') {
            $payload['api_key'] = $this->apiKey;
        }

        try {
            $response = Http::timeout($timeoutSeconds ?? 10)
                ->retry($retries ?? self::RETRY_TIMES, self::RETRY_SLEEP_MS, fn (\Throwable $e) => $e instanceof ConnectionException, false)
                ->post($this->endpoint, $payload);
            $resp = $response->json();

            if ($response->failed() || ! isset($resp['translatedText'])) {
                return [
                    'translated' => $text,
                    'detected' => $from,
                    'failed' => true,
                ];
            }

            return [
                'translated' => $resp['translatedText'],
                'detected' => $resp['detectedLanguage']['language'] ?? $from,
            ];
        } catch (\Throwable) {
            return [
                'translated' => $text,
                'detected' => $from,
                'failed' => true,
            ];
        }
    }

    /**
     * @return array{translated: string, detected: string, mocked: bool}
     */
    private function mockResponse(string $text, string $from, string $to): array
    {
        return [
            'translated' => '['.$to.'] '.$text,
            'detected' => $from === 'auto' ? 'es' : $from,
            'mocked' => true,
        ];
    }
}
