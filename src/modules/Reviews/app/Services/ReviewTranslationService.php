<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Models\Review;

class ReviewTranslationService
{
    private const DEEPL_API_URL = 'https://api-free.deepl.com/v2/translate';

    private const CACHE_TTL_SECONDS = 86400; // 24 hours

    /**
     * Translate a single text string via DeepL.
     * Returns the original text if the API key is not configured or the call fails.
     */
    public function translate(string $text, string $targetLang = 'ES', ?string $sourceLang = null): string
    {
        if (! $this->isConfigured() || trim($text) === '') {
            return $text;
        }

        try {
            $payload = [
                'text' => [$text],
                'target_lang' => strtoupper($targetLang),
            ];

            if ($sourceLang !== null) {
                $payload['source_lang'] = strtoupper($sourceLang);
            }

            $response = Http::withHeaders([
                'Authorization' => 'DeepL-Auth-Key '.config('reviews.general.deepl.api_key'),
                'Content-Type' => 'application/json',
            ])->timeout(15)->post(self::DEEPL_API_URL, $payload);

            if ($response->failed()) {
                Log::warning('DeepL API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $text;
            }

            return $response->json('translations.0.text', $text);
        } catch (\Throwable $e) {
            Log::error('ReviewTranslationService::translate error: '.$e->getMessage());

            return $text;
        }
    }

    /**
     * Translate a review's comment, using a 24-hour cache.
     *
     * @return array{original: string, translated: string, detected_lang: string|null, target_lang: string}
     */
    public function translateReview(Review $review, string $targetLang = 'ES'): array
    {
        $targetLang = strtoupper($targetLang);
        $cacheKey = "review_translation:{$review->id}:{$targetLang}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($review, $targetLang): array {
            $original = $review->comment ?? '';

            if (! $this->isConfigured() || trim($original) === '') {
                return [
                    'original' => $original,
                    'translated' => $original,
                    'detected_lang' => null,
                    'target_lang' => $targetLang,
                ];
            }

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'DeepL-Auth-Key '.config('reviews.general.deepl.api_key'),
                    'Content-Type' => 'application/json',
                ])->timeout(15)->post(self::DEEPL_API_URL, [
                    'text' => [$original],
                    'target_lang' => $targetLang,
                ]);

                if ($response->failed()) {
                    Log::warning('DeepL API error on translateReview', [
                        'review_id' => $review->id,
                        'status' => $response->status(),
                    ]);

                    return [
                        'original' => $original,
                        'translated' => $original,
                        'detected_lang' => null,
                        'target_lang' => $targetLang,
                    ];
                }

                $translated = $response->json('translations.0.text', $original);
                $detectedLang = $response->json('translations.0.detected_source_language');

                return [
                    'original' => $original,
                    'translated' => $translated,
                    'detected_lang' => $detectedLang,
                    'target_lang' => $targetLang,
                ];
            } catch (\Throwable $e) {
                Log::error('ReviewTranslationService::translateReview error: '.$e->getMessage());

                return [
                    'original' => $original,
                    'translated' => $original,
                    'detected_lang' => null,
                    'target_lang' => $targetLang,
                ];
            }
        });
    }

    /**
     * Whether a DeepL API key is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty(config('reviews.general.deepl.api_key'));
    }
}
