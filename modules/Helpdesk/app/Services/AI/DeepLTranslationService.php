<?php

namespace Modules\Helpdesk\Services\AI;

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

        $apiKey = config('services.deepl.key');

        if (empty($apiKey)) {
            return null;
        }

        $cacheKey = 'helpdesk:ai:translation:'.md5($text.$targetLang.($sourceLang ?? ''));

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($text, $targetLang, $sourceLang, $apiKey) {
            return $this->callDeepL($text, strtoupper($targetLang), $sourceLang ? strtoupper($sourceLang) : null, $apiKey);
        });
    }

    private function callDeepL(string $text, string $targetLang, ?string $sourceLang, string $apiKey): ?string
    {
        $baseUrl = config('services.deepl.url', 'https://api-free.deepl.com');

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
