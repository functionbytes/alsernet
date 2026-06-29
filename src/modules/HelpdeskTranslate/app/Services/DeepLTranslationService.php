<?php

namespace Modules\HelpdeskTranslate\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Setting;

class DeepLTranslationService
{
    /** Memoized API key for the duration of this container instance. */
    private ?string $apiKey = null;

    /** Memoized base URL for the duration of this container instance. */
    private ?string $baseUrl = null;

    /**
     * Translate text via DeepL.
     * No Cache::remember here — CachedTranslator owns the DB-level cache layer.
     */
    public function translate(string $text, string $targetLang = 'ES', ?string $sourceLang = null): ?string
    {
        if (empty(trim($text))) {
            return null;
        }

        $apiKey = $this->resolveApiKey();

        if (empty($apiKey)) {
            return null;
        }

        return $this->callDeepL($text, strtoupper($targetLang), $sourceLang ? strtoupper($sourceLang) : null, $apiKey);
    }

    /**
     * Translate + return the detected source language. Used by detection
     * flows where we want to know what language the visitor wrote in.
     * Cached to avoid burning DeepL quota on repeated detection snippets.
     *
     * @return array{translated: ?string, detected_source_language: ?string}
     */
    public function translateWithDetection(string $text, string $targetLang = 'ES'): array
    {
        $apiKey = $this->resolveApiKey();
        if (empty($apiKey) || trim($text) === '') {
            return ['translated' => null, 'detected_source_language' => null];
        }

        $cacheKey = 'helpdesk:ai:detect:'.md5($text.$targetLang);

        $result = Cache::remember($cacheKey, now()->addHours(24), function () use ($text, $targetLang, $apiKey) {
            try {
                $response = Http::withHeaders(['Authorization' => "DeepL-Auth-Key {$apiKey}"])
                    ->timeout(15)
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

    private function callDeepL(string $text, string $targetLang, ?string $sourceLang, string $apiKey): ?string
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
                ->timeout(15)
                ->post("{$baseUrl}/v2/translate", $payload);

            if ($response->failed()) {
                Log::warning('DeepLTranslationService: translation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json('translations.0.text');
        } catch (\Throwable $e) {
            Log::error('DeepLTranslationService: exception', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
