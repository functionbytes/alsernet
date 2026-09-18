<?php

namespace Modules\Media\Drivers\AutoTagging;

use Illuminate\Support\Facades\Http;
use Modules\Media\Contracts\AutoTaggingDriver;

class GoogleVisionDriver implements AutoTaggingDriver
{
    public function __construct(private readonly string $apiKey) {}

    public function detectTags(string $imagePath): array
    {
        $base64 = base64_encode(file_get_contents($imagePath));

        $response = Http::timeout(30)
            ->post("https://vision.googleapis.com/v1/images:annotate?key={$this->apiKey}", [
                'requests' => [[
                    'image' => ['content' => $base64],
                    'features' => [['type' => 'LABEL_DETECTION', 'maxResults' => 10]],
                ]],
            ]);

        if (! $response->successful()) {
            return [];
        }

        $annotations = $response->json('responses.0.labelAnnotations', []);

        return array_slice(
            array_map(fn ($a) => (string) ($a['description'] ?? ''), $annotations),
            0,
            10
        );
    }
}
