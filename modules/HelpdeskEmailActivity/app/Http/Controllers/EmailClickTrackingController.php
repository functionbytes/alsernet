<?php

namespace Modules\HelpdeskEmailActivity\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Models\EmailLogClick;
use Modules\HelpdeskEmailActivity\Models\EmailLogLink;
use Throwable;

/**
 * Redirección de clic — ruta pública sin auth (la sigue el cliente de correo
 * o el navegador del destinatario al pulsar un enlace, no un usuario del
 * panel). Cada hit registra una fila nueva (no dedup, mismo criterio que
 * EmailOpenTrackingController) en email_log_clicks, para no perder el
 * historial de repeticiones sobre el mismo enlace.
 *
 * El tracking nunca debe impedir que el destinatario llegue a su destino:
 * cualquier fallo al registrar se traga y la redirección ocurre igual. Un
 * token inexistente, o que no pertenezca al $emailLog de la URL, o cuya URL
 * guardada no sea http(s) (defensa en profundidad — nunca debería ocurrir,
 * ver LogEmailQueued::injectClickTracking()) aborta con 404 en vez de
 * arriesgar una redirección abierta.
 */
class EmailClickTrackingController extends Controller
{
    public function redirect(Request $request, EmailLog $emailLog, string $token): RedirectResponse
    {
        $link = EmailLogLink::query()
            ->where('email_log_id', $emailLog->id)
            ->where('token', $token)
            ->first();

        abort_if(! $link || ! Str::startsWith($link->url, ['http://', 'https://']), 404);

        try {
            EmailLogClick::create([
                'email_log_link_id' => $link->id,
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 512),
                'clicked_at' => now(),
            ]);
        } catch (Throwable) {
            // Silencioso a propósito: nunca debe fallar la redirección real.
        }

        return redirect()->away($link->url);
    }
}
