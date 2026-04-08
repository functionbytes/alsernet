<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepLTranslationService
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.deepl.key', '');
        $this->baseUrl = rtrim(config('services.deepl.url', 'https://api-free.deepl.com'), '/');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Translate one or multiple texts via DeepL.
     *
     * @param  string|string[]  $text
     * @return string|string[]
     *
     * @throws \RuntimeException
     */
    public function translate(string|array $text, string $targetLang, ?string $sourceLang = null): string|array
    {
        $isArray = is_array($text);
        $texts = $isArray ? $text : [$text];

        $payload = array_filter([
            'auth_key' => $this->apiKey,
            'text' => $texts,
            'target_lang' => strtoupper($targetLang),
            'source_lang' => $sourceLang ? strtoupper($sourceLang) : null,
            'tag_handling' => 'html',
        ]);

        $response = Http::timeout(30)->post("{$this->baseUrl}/v2/translate", $payload);

        if (! $response->successful()) {
            Log::error('DeepL translation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'target_lang' => $targetLang,
            ]);

            throw new \RuntimeException('Error al comunicarse con la API de DeepL (status '.$response->status().')');
        }

        $translations = collect($response->json('translations'))->pluck('text')->all();

        return $isArray ? $translations : $translations[0];
    }

    /**
     * Fetch the list of supported target languages, cached for 24 hours.
     *
     * @return array<int, array{language: string, name: string}>
     */
    public function getSupportedLanguages(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        return cache()->remember('deepl.languages', now()->addDay(), function () {
            $response = Http::timeout(10)->get("{$this->baseUrl}/v2/languages", [
                'auth_key' => $this->apiKey,
                'type' => 'target',
            ]);

            return $response->successful() ? $response->json() : [];
        });
    }
}
