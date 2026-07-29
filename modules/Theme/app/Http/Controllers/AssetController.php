<?php

namespace Modules\Theme\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends Controller
{
    /**
     * Serve theme assets directly from modules/Theme/public/theme
     * This allows live editing of theme assets without publishing to public/
     */
    public function asset(string $path): BinaryFileResponse|Response
    {
        // Security: prevent directory traversal
        $cleanPath = str_replace(['..', '\\'], ['', '/'], $path);
        $fullPath = module_path('Theme', "public/theme/{$cleanPath}");

        // Verify file exists and is within theme directory
        if (!file_exists($fullPath) || !str_starts_with(realpath($fullPath), realpath(module_path('Theme', 'public/theme')))) {
            return response('Asset not found', 404);
        }

        // Determine MIME type
        $mimeType = $this->getMimeType($fullPath);

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000', // Cache for 1 year
        ]);
    }

    /**
     * Get MIME type for file
     */
    private function getMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'otf' => 'font/otf',
            default => 'application/octet-stream',
        };
    }
}
