<?php

namespace Modules\Media\Drivers\AutoCaption;

use Illuminate\Support\Facades\Http;
use Modules\Media\Contracts\AutoCaptionDriver;

class OpenAIDriver implements AutoCaptionDriver
{
    public function __construct(private readonly string $apiKey) {}

    public function generateCaption(string $imagePath): ?string
    {
        $mimeType = mime_content_type($imagePath);
        $base64 = base64_encode(file_get_contents($imagePath));
        $dataUrl = "data:{$mimeType};base64,{$base64}";

        $response = Http::timeout(30)
            ->withToken($this->apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'max_tokens' => 150,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'image_url', 'image_url' => ['url' => $dataUrl, 'detail' => 'low']],
                        ['type' => 'text', 'text' => 'Write a concise alt text caption for this image (max 120 characters). Reply ONLY with the caption text, no quotes.'],
                    ],
                ]],
            ]);

        if (! $response->successful()) {
            return null;
        }

        $caption = trim($response->json('choices.0.message.content', ''));

        return $caption !== '' ? mb_substr($caption, 0, 120) : null;
    }
}
