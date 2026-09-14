<?php

namespace Modules\Media\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Media\Models\MediaFile;

class MediaEmbedController extends Controller
{
    public function generateEmbedCode(MediaFile $file, Request $request): JsonResponse
    {
        $this->authorize('view', $file);

        $request->validate([
            'ttl_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $ttl = $request->integer('ttl_days', 30);
        $expiresAt = now()->addDays($ttl);

        $payload = [
            'id' => $file->id,
            'exp' => $expiresAt->timestamp,
            'sig' => hash_hmac(
                'sha256',
                "embed:{$file->id}|{$expiresAt->timestamp}",
                config('app.key')
            ),
        ];

        $token = strtr(rtrim(base64_encode(json_encode($payload)), '='), '+/', '-_');

        $embedCode = sprintf(
            '<script data-media-embed="%d" data-media-token="%s" data-media-endpoint="%s" src="%s"></script>',
            $file->id,
            $token,
            url('/'),
            url('/modules/Media/js/embed.js')
        );

        return response()->json([
            'success' => true,
            'embed_code' => $embedCode,
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }
}
