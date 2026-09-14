<?php

namespace Modules\System\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceMiddleware
{
    private array $excludedRoutes = [
        'settings/*',
        'settings',
        'livechat/*',
        'image/*',
        'imagedownload/*',
        'emailtoticket/*',
        'emtcimageurl/*',
        'emtcimagedownload/*',
        'emailtoticketdownload/*',
        'getProfile/*',
        'getimage/*',
        'notificationsreading',
        'badgecount',
        'markasreadcount',
        'notificationalerts',
        'timeupdate',
        'captcha',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $installed = Cache::remember('app.installed', 3600, fn () => Schema::hasTable('settings'));

        if (! $installed) {
            return $next($request);
        }

        if (setting('MAINTENANCE_MODE') == 'on' && ! $this->isExcludedRoute($request)) {
            return response()->view('errors.503', [], 503);
        }

        return $next($request);
    }

    private function isExcludedRoute(Request $request): bool
    {
        foreach ($this->excludedRoutes as $route) {
            if (Str::is($route, $request->path())) {
                return true;
            }
        }

        return false;
    }
}
