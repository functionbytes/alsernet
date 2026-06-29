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

        $contentType = str_ends_with($relativePath, '.css') ? 'text/css' : 'application/javascript';

        return response()->file($absolutePath, [
            'Content-Type' => $contentType.'; charset=utf-8',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
