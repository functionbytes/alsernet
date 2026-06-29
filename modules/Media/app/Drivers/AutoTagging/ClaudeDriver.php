<?php

namespace Modules\Media\Drivers\AutoTagging;

use Illuminate\Support\Facades\Http;
use Modules\Media\Contracts\AutoTaggingDriver;

class ClaudeDriver implements AutoTaggingDriver
{
    public function __construct(private readonly string $apiKey) {}

    public function detectTags(string $imagePath): array
    {
        $mimeType = mime_content_type($imagePath);
        $base64 = base64_encode(file_get_contents($imagePath));

        $response = Http::timeout(30)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => '.claude-haiku-4-5',
                'max_tokens' => 200,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'image', 'source' => [
                            'type' => 'base64',
                            'media_type' => $mimeType,
                            'data' => $base64,
                        ]],
                        ['type' => 'text', 'text' => 'Lista 5-10 tags cortos (1-2 palabras) que describan esta imagen. Responde SOLO JSON array de strings, sin explicaciones.'],
                    ],
                ]],
            ]);

        if (! $response->successful()) {
            return [];
        }

        $text = $response->json('content.0.text', '[]');
        $json = trim(preg_replace('/```json|```/', '', $text));
        $tags = json_decode($json, true) ?: [];

        return array_slice(array_map('strval', $tags), 0, 10);
    }
}
