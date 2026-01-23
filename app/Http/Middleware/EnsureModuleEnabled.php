<?php

namespace App\Http\Middleware;

use App\Helpers\ModuleStatusHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $moduleName): Response
    {
        if (! ModuleStatusHelper::isModuleEnabled($moduleName)) {
            abort(404, "Module {$moduleName} is not available");
        }

        return $next($request);
    }
}
