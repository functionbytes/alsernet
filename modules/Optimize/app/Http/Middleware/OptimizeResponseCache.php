<?php

namespace Modules\Optimize\Http\Middleware;

use Closure;
use Illuminate\Cache\TaggedCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Setting;
use Symfony\Component\HttpFoundation\Response;

class OptimizeResponseCache
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') || $request->is('setting*') || $request->expectsJson()) {
            return $next($request);
        }

        $key = 'optimize.cache.'.md5($request->fullUrl().'|'.(string) auth()->id());
        $ttl = (int) Setting::get('optimize.response_cache_ttl', '60');
        $store = $this->taggedStore();

        if ($store) {
            $cached = $store->get($key);

            if ($cached !== null) {
                return response($cached, 200, [
                    'Content-Type' => 'text/html; charset=UTF-8',
                    'X-Optimize-Cache' => 'HIT',
                ]);
            }
        }

        $response = $next($request);

        $contentType = $response->headers->get('Content-Type', '');

        if ($store && $response->getStatusCode() === 200 && (str_contains($contentType, 'text/html') || empty($contentType))) {
            $store->put($key, $response->getContent(), now()->addMinutes($ttl));
        }

        return $response;
    }

    private function taggedStore(): ?TaggedCache
    {
        try {
            return Cache::tags(['optimize.response']);
        } catch (\BadMethodCallException) {
            return null;
        }
    }
}
