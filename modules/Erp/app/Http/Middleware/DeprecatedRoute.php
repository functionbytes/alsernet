<?php

namespace Modules\Erp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marca una ruta como obsoleta (RFC 9745) e indica su sucesora, sin cambiar
 * la respuesta: los clientes antiguos siguen funcionando mientras migran.
 *
 *   ->middleware('erp.deprecated:/api/erp/v2/endpoints')
 */
class DeprecatedRoute
{
    public function handle(Request $request, Closure $next, string $successor = ''): Response
    {
        $response = $next($request);

        $response->headers->set('Deprecation', 'true');
        if ($successor !== '') {
            $response->headers->set('Link', '<'.$successor.'>; rel="successor-version"');
        }

        return $response;
    }
}
