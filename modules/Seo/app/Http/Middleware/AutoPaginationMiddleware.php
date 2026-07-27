<?php

namespace Modules\Seo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Seo\Services\SeoService;

class AutoPaginationMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        // OJO: usar $request->has('page') aquí forzaba un parseo completo de
        // $request->all() (incluye archivos subidos vía allFiles()) en CADA
        // request, incluso los que no tienen nada que ver con paginación. Como
        // $next($request) ya corrió arriba, para entonces un archivo subido en
        // este mismo request ya pudo haber sido movido a su destino final
        // (ej. Spatie MediaLibrary) — re-envolver esa ruta de archivo ya
        // inexistente lanzaba FileNotFoundException y devolvía 500 aunque la
        // subida ya se hubiera guardado correctamente. "page" es siempre un
        // parámetro de query string, así que basta con mirar la query bag.
        if (! $request->query->has('page')) {
            return $response;
        }

        $page = (int) $request->query('page', 1);

        /** @var SeoService $seo */
        $seo = app('seo');

        if (! method_exists($seo, 'setPagination')) {
            return $response;
        }

        $prevUrl = '';
        if ($page > 1) {
            $prevUrl = $page === 2
                ? $request->url()
                : $request->fullUrlWithQuery(['page' => $page - 1]);
        }

        $nextUrl = $request->fullUrlWithQuery(['page' => $page + 1]);

        $seo->setPagination($prevUrl, $nextUrl);

        return $response;
    }
}
