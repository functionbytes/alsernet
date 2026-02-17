<?php

namespace Modules\Page\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Page\Models\Page;
use Symfony\Component\HttpFoundation\Response;

class CheckPagePublished
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');

        if ($slug) {
            $page = Page::where('slug', $slug)->first();

            if ($page && ! $page->isPublished()) {
                // Check if user is authenticated and has permission
                if (! auth()->check()) {
                    abort(404);
                }

                // You can add additional permission checks here
                // if (!auth()->user()->can('view unpublished pages')) {
                //     abort(403);
                // }
            }
        }

        return $next($request);
    }
}
