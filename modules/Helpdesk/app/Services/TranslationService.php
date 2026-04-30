<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TranslationService
{
    private string $endpoint;

    private string $apiKey;

    public function __construct()
    {
        $this->endpoint = config('helpdesk.translation.endpoint', '');
        $this->apiKey = config('helpdesk.translation.api_key', '');
    }

    /**
     * Translate text using LibreTranslate.
     *
     * @return array{translated: string, detected: string, mocked?: bool}
     */
    public function translate(string $text, string $from = 'auto', string $to = 'es'): array
    {
        if (empty($this->endpoint)) {
            return $this->mockResponse($text, $from, $to);
        }

        $cacheKey = 'translation:'.md5($text.$from.$to);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($text, $from, $to) {
            return $this->callLibreTranslate($text, $from, $to);
        });
    }

    /**
     * @return array{translated: string, detected: string}
     */
    private function callLibreTranslate(string $text, string $from, string $to): array
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

        $response = Http::timeout(10)->post($this->endpoint, $payload);
        $resp = $response->json();

        return [
            'translated' => $resp['translatedText'] ?? $text,
            'detected' => $resp['detectedLanguage']['language'] ?? $from,
        ];
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
