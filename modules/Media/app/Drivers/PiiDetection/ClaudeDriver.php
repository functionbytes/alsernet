<?php

namespace Modules\Media\Drivers\PiiDetection;

use Illuminate\Support\Facades\Http;
use Modules\Media\Contracts\PiiDetectionDriver;

class ClaudeDriver implements PiiDetectionDriver
{
    private const PROMPT = 'Analyze this image for personally identifiable information (PII). '
        .'Detect: faces, ID cards, passports, credit cards, license plates, signatures, addresses, phone numbers. '
        .'Reply ONLY with JSON: {"has_pii": bool, "types": ["face", "id_card", ...]}. No explanation.';

    public function __construct(private readonly string $apiKey) {}

    public function detectPii(string $filePath): array
    {
        $mimeType = mime_content_type($filePath);
        $base64 = base64_encode(file_get_contents($filePath));

        $response = Http::timeout(30)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => '.claude-haiku-4-5',
                'max_tokens' => 150,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'image', 'source' => [
                            'type' => 'base64',
                            'media_type' => $mimeType,
                            'data' => $base64,
                        ]],
                        ['type' => 'text', 'text' => self::PROMPT],
                    ],
                ]],
            ]);

        if (! $response->successful()) {
            return ['has_pii' => false, 'types' => []];
        }

        $text = $response->json('content.0.text', '{}');
        $json = trim(preg_replace('/```json|```/', '', $text));
        $result = json_decode($json, true);

        if (! is_array($result)) {
            return ['has_pii' => false, 'types' => []];
        }

        return [
            'has_pii' => (bool) ($result['has_pii'] ?? false),
            'types' => array_map('strval', $result['types'] ?? []),
        ];
    }
}
