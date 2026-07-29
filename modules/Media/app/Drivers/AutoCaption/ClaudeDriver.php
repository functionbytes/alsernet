<?php

namespace Modules\Media\Drivers\AutoCaption;

use Illuminate\Support\Facades\Http;
use Modules\Media\Contracts\AutoCaptionDriver;

class ClaudeDriver implements AutoCaptionDriver
{
    public function __construct(private readonly string $apiKey) {}

    public function generateCaption(string $imagePath): ?string
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
                'model' => 'claude-haiku-4-5',
                'max_tokens' => 150,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'image', 'source' => [
                            'type' => 'base64',
                            'media_type' => $mimeType,
                            'data' => $base64,
                        ]],
                        ['type' => 'text', 'text' => 'Write a concise alt text caption for this image (max 120 characters). Reply ONLY with the caption text, no quotes.'],
                    ],
                ]],
            ]);

        if (! $response->successful()) {
            return null;
        }

        $caption = trim($response->json('content.0.text', ''));

        return $caption !== '' ? mb_substr($caption, 0, 120) : null;
    }
}
