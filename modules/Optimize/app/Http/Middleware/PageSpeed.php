<?php

namespace Modules\Optimize\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Setting;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class PageSpeed
{
    /** Per-request cache so all middleware share one Setting::get call. */
    private static ?bool $globalEnabled = null;

    /** Cached custom skip patterns parsed from settings. */
    private static ?array $customSkipPatterns = null;

    abstract public function apply(string $buffer): string;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldProcess($request, $response)) {
            return $response;
        }

        $before = strlen($response->getContent());
        $content = $this->apply($response->getContent());
        $after = strlen($content);

        // Track pages processed (once per request regardless of how many middleware run)
        if (! $request->attributes->has('_optimize_tracked')) {
            Cache::increment('optimize.stats.requests');
            Cache::increment('optimize.stats.daily.'.date('Y-m-d').'.requests');
            $request->attributes->set('_optimize_tracked', true);
        }

        if ($before > $after) {
            $delta = $before - $after;
            Cache::increment('optimize.stats.bytes_saved', $delta);
            Cache::increment('optimize.stats.daily.'.date('Y-m-d').'.bytes_saved', $delta);
        }

        return $response->setContent($content);
    }

    protected function replace(array $replace, string $buffer): string
    {
        return preg_replace(array_keys($replace), array_values($replace), $buffer) ?? $buffer;
    }

    protected function shouldProcess(Request $request, mixed $response): bool
    {
        // Cache the master toggle across all middleware instances in the same request
        static::$globalEnabled ??= Setting::get('optimize.enabled', '0') === '1';

        if (! static::$globalEnabled) {
            return false;
        }

        if ($request->is('setting*')) {
            return false;
        }

        if ($request->expectsJson()) {
            return false;
        }

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type', '');

        if (! str_contains($contentType, 'text/html') && ! empty($contentType)) {
            return false;
        }

        foreach (config('Optimize.general.skip', []) as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        static::$customSkipPatterns ??= array_values(array_filter(
            array_map('trim', explode("\n", Setting::get('optimize.skip_patterns', '')))
        ));

        foreach (static::$customSkipPatterns as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        return true;
    }

    /** Reset cache between requests (used in tests). */
    public static function resetCache(): void
    {
        static::$globalEnabled = null;
        static::$customSkipPatterns = null;
    }
}
