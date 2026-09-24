<?php

namespace Modules\HelpdeskTickets\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hace cumplir en el servidor los interruptores de Settings → Funcionalidades
 * de tickets. Hasta el 24-sep-2026 solo ocultaban el botón: el endpoint
 * seguía abierto para cualquiera que lo llamara (atajo de teclado, otra
 * pestaña con la página vieja, una petición a mano).
 *
 * Uso: ->middleware(EnsureTicketFeatureEnabled::class.':action_merge')
 */
class EnsureTicketFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (function_exists('helpdesk_ticket_feature_enabled') && ! helpdesk_ticket_feature_enabled($feature)) {
            $message = 'Esta funcionalidad está desactivada en la configuración de tickets.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            abort(403, $message);
        }

        return $next($request);
    }
}
