<?php

namespace Modules\Seo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Seo\Models\Seo404Log;
use Symfony\Component\HttpFoundation\Response;

class Track404Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 404) {
            return $response;
        }

        $path = $request->path();

        if (
            str_starts_with($path, 'api/') ||
            str_starts_with($path, 'setting/') ||
            preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|map)$/i', $path)
        ) {
            return $response;
        }

        if ($this->isBot($request)) {
            return $response;
        }

        Seo404Log::recordHit(
            '/'.$path,
            $request->header('referer'),
            $request->ip(),
            $request->userAgent()
        );

        return $response;
    }

    private function isBot(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        $bots = ['bot', 'crawler', 'spider', 'scraper', 'wget', 'curl', 'python', 'java/', 'go-http', 'libwww'];

        foreach ($bots as $bot) {
            if (str_contains($userAgent, $bot)) {
                return true;
            }
        }

        return false;
    }
}
