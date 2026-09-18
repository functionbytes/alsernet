<?php

namespace Modules\Media\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rewrites JPEG/PNG/GIF image responses to WebP or AVIF on-the-fly when the
 * browser advertises support via `Accept`. Converted files are cached on disk
 * to avoid reencoding on every request.
 *
 * Enable by adding `media.modern-images` to the routes that serve images,
 * e.g. the existing `/storage/...` or `/media/...` endpoints.
 */
class ModernImageMiddleware
{
    /**
     * Formats by preference — AVIF first (better compression) then WebP.
     *
     * @var array<int, array{format: string, accept: string, quality: int}>
     */
    private const FORMATS = [
        ['format' => 'avif', 'accept' => 'image/avif', 'quality' => 50],
        ['format' => 'webp', 'accept' => 'image/webp', 'quality' => 75],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldConvert($request, $response)) {
            return $response;
        }

        $sourcePath = $this->extractFilePath($response);
        if (! $sourcePath || ! is_file($sourcePath)) {
            return $response;
        }

        $format = $this->pickFormat($request);
        if (! $format) {
            return $response;
        }

        $converted = $this->convert($sourcePath, $format);
        if (! $converted) {
            return $response;
        }

        return response()->file($converted, [
            'Content-Type' => 'image/'.$format['format'],
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Vary' => 'Accept',
        ]);
    }

    private function shouldConvert(Request $request, Response $response): bool
    {
        if (! class_exists(ImageManager::class)) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        return (bool) preg_match('#^image/(jpeg|png|gif)#i', $contentType);
    }

    private function extractFilePath(Response $response): ?string
    {
        if ($response instanceof BinaryFileResponse) {
            return $response->getFile()->getRealPath() ?: null;
        }

        return null;
    }

    /**
     * @return array{format: string, accept: string, quality: int}|null
     */
    private function pickFormat(Request $request): ?array
    {
        $accept = (string) $request->header('Accept', '');

        foreach (self::FORMATS as $fmt) {
            if (str_contains($accept, $fmt['accept'])) {
                return $fmt;
            }
        }

        return null;
    }

    /**
     * @param  array{format: string, accept: string, quality: int}  $format
     */
    private function convert(string $sourcePath, array $format): ?string
    {
        $cacheDir = storage_path('app/modern-images');
        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $cacheKey = md5($sourcePath.filemtime($sourcePath).$format['format']);
        $cachePath = $cacheDir.DIRECTORY_SEPARATOR.$cacheKey.'.'.$format['format'];

        if (is_file($cachePath)) {
            return $cachePath;
        }

        try {
            $manager = new ImageManager(new Driver);
            $image = $manager->read($sourcePath);

            $encoded = match ($format['format']) {
                'webp' => $image->toWebp($format['quality']),
                'avif' => $image->toAvif($format['quality']),
                default => null,
            };

            if (! $encoded) {
                return null;
            }

            file_put_contents($cachePath, (string) $encoded);

            return $cachePath;
        } catch (\Throwable $e) {
            Cache::forget('media.modern-images.error.'.$cacheKey);

            return null;
        }
    }
}
