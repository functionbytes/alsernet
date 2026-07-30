<?php

namespace Modules\Helpdesk\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepLTranslationService
{
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
                    ->retry(2, 200, fn (\Throwable $e) => $e instanceof ConnectionException, false)
                    ->post($this->resolveBaseUrl().'/v2/translate', [
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

        if ($result === null) {
            Cache::forget($cacheKey);

            return ['translated' => null, 'detected_source_language' => null];
        }

        return $result;
    }

    private function resolveApiKey(): ?string
    {
        return config('services.deepl.key') ?: null;
    }

    private function resolveBaseUrl(): string
    {
        return config('services.deepl.url', 'https://api-free.deepl.com');
    }

    private function callDeepL(string $text, string $targetLang, ?string $sourceLang, string $apiKey): ?string
    {
        $payload = ['text' => [$text], 'target_lang' => $targetLang];

        if ($sourceLang) {
            $payload['source_lang'] = $sourceLang;
        }

        try {
            $response = Http::withHeaders(['Authorization' => "DeepL-Auth-Key {$apiKey}"])
                ->timeout(15)
                ->retry(2, 200, fn (\Throwable $e) => $e instanceof ConnectionException, false)
                ->post($this->resolveBaseUrl().'/v2/translate', $payload);

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
