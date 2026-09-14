<?php

namespace Modules\Helpdesk\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate de un solo punto para el toggle de "Settings > Integraciones" de un
 * modulo satelite Helpdesk (helpdesk_{key}_enabled() ya combina modulo
 * instalado + config kill-switch + el toggle admin). Se aplica al grupo
 * completo de rutas del modulo en su ServiceProvider — antes cada controller
 * repetia (o se olvidaba de repetir) un abort_if() a mano solo en index(),
 * dejando acciones mutadoras (store/update/destroy/takeOver/...) alcanzables
 * aunque el admin hubiera desactivado la integracion.
 *
 * Uso: Route::middleware('integration.enabled:chatflow')->group(...)
 * llama a helpdesk_chatflow_enabled().
 */
class EnsureIntegrationEnabled
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        $helper = "helpdesk_{$key}_enabled";

        if (function_exists($helper) && ! $helper()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta integración está desactivada.',
                    'code' => 'INTEGRATION_DISABLED',
                ], 404);
            }

            abort(404);
        }

        return $next($request);
    }
}
