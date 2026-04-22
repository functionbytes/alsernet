<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Security headers applied to every response.
     *
     * @var array<string, string>
     */
    private array $headers = [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach ($this->headers as $header => $value) {
            $response->headers->set($header, $value);
        }

        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        if ($this->shouldBlockIndexing($request)) {
            $response->headers->set(
                'X-Robots-Tag',
                'noindex, nofollow, noarchive, nosnippet'
            );
        }

        return $response;
    }

    private function shouldBlockIndexing(Request $request): bool
    {
        $path = $request->path();

        foreach (['panel', 'panel/*', 'admin', 'admin/*', 'login', 'logout', 'register', 'password/*', 'api/*', 'auth/*'] as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return str_starts_with($path, 'panel')
            || str_starts_with($path, 'admin')
            || str_starts_with($path, 'auth');
    }
}
