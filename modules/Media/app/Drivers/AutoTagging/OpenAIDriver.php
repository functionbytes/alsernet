<?php

namespace Modules\Media\Drivers\AutoTagging;

use Illuminate\Support\Facades\Http;
use Modules\Media\Contracts\AutoTaggingDriver;

class OpenAIDriver implements AutoTaggingDriver
{
    public function __construct(private readonly string $apiKey) {}

    public function detectTags(string $imagePath): array
    {
        $mimeType = mime_content_type($imagePath);
        $base64 = base64_encode(file_get_contents($imagePath));
        $dataUrl = "data:{$mimeType};base64,{$base64}";

        $response = Http::timeout(30)
            ->withToken($this->apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'max_tokens' => 200,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'image_url', 'image_url' => ['url' => $dataUrl, 'detail' => 'low']],
                        ['type' => 'text', 'text' => 'List 5-10 short tags (1-2 words) describing this image. Reply ONLY with a JSON array of strings, no explanation.'],
                    ],
                ]],
            ]);

        if (! $response->successful()) {
            return [];
        }

        $text = $response->json('choices.0.message.content', '[]');
        $json = trim(preg_replace('/```json|```/', '', $text));
        $tags = json_decode($json, true) ?: [];

        return array_slice(array_map('strval', $tags), 0, 10);
    }
}
