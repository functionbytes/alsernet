<?php

namespace Modules\Engagement\Http\Controllers\Api\Sdk;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves engagement / livechat-widget bundles with CORS headers so they can be
 * loaded as ES modules from third-party origins (PrestaShop, Shopify, etc.).
 * Nginx serves /build-* directly without going through Laravel, so the
 * HandleCors middleware never runs on those paths.
 */
class AssetProxyController extends Controller
{
    private const ALLOWED_BUNDLES = [
        'engagement-sdk' => 'build-engagement/sdk.js',
        'engagement-worker' => 'build-engagement/sdk-worker.js',
        'livechat-widget-loader' => 'build-helpdesklivechat/widget.js',
        'livechat-widget' => 'build-helpdesklivechat/widget/main.js',
        'livechat-widget-css' => 'build-helpdesklivechat/widget/main.css',
    ];

    /**
     * Fixed local directory that holds the Vite-emitted lazy chunks for the
     * livechat widget bundle. Never derived from user input.
     */
    private const CHUNKS_DIRECTORY = 'build-helpdesklivechat/widget/chunks';

    public function __invoke(string $bundle): BinaryFileResponse|Response
    {
        if (! isset(self::ALLOWED_BUNDLES[$bundle])) {
            abort(404);
        }

        $relativePath = self::ALLOWED_BUNDLES[$bundle];
        $absolutePath = public_path($relativePath);

        if (! file_exists($absolutePath)) {
            abort(404);
        }

        return $this->fileResponse($absolutePath, $relativePath);
    }

    /**
     * Serves a lazy chunk emitted alongside the livechat widget bundle. The
     * route's `where('file', ...)` constraint already forbids slashes, and
     * `basename()` is checked again here as defense-in-depth against path
     * traversal — the file is always resolved inside CHUNKS_DIRECTORY.
     */
    public function chunk(string $file): BinaryFileResponse|Response
    {
        if (basename($file) !== $file) {
            abort(404);
        }

        $absolutePath = public_path(self::CHUNKS_DIRECTORY.'/'.$file);

        if (! file_exists($absolutePath)) {
            abort(404);
        }

        return $this->fileResponse($absolutePath, $file);
    }

    private function fileResponse(string $absolutePath, string $path): BinaryFileResponse
    {
        $contentType = str_ends_with($path, '.css') ? 'text/css' : 'application/javascript';

        return response()->file($absolutePath, [
            'Content-Type' => $contentType.'; charset=utf-8',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
