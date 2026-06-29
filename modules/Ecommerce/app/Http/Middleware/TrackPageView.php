<?php

namespace Modules\Ecommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\PageView;
use Modules\Ecommerce\Models\Product;
use Symfony\Component\HttpFoundation\Response;

class TrackPageView
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldTrack($request)) {
            return $response;
        }

        try {
            PageView::query()->create([
                'session_id' => session()->getId(),
                'customer_id' => auth('ecommerce')->id(),
                'url' => $request->fullUrl(),
                'referrer' => $request->headers->get('referer'),
                'product_id' => $this->resolveProductId($request),
                'user_agent' => substr((string) $request->headers->get('user-agent'), 0, 500),
                'viewed_at' => now(),
            ]);
        } catch (\Throwable) {
            // Silent fail — tracking must never break the request
        }

        return $response;
    }

    private function shouldTrack(Request $request): bool
    {
        return $request->isMethod('GET')
            && ! $request->expectsJson()
            && str_starts_with($request->path(), 'tienda')
            && ! str_contains($request->path(), 'api/');
    }

    private function resolveProductId(Request $request): ?int
    {
        if ($request->route()?->getName() !== 'shop.product') {
            return null;
        }

        $slug = $request->route('slug');

        return Product::query()->where('slug', $slug)->value('id');
    }
}
