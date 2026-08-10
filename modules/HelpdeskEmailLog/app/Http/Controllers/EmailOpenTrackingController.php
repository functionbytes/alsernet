<?php

namespace Modules\HelpdeskEmailLog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Models\EmailLogOpen;
use Throwable;

/**
 * Pixel de apertura — ruta pública sin auth (la abre el cliente de correo del
 * destinatario, no un usuario del panel). Registra CADA hit como una fila
 * nueva (no dedup): "2 aperturas · última 14:22" se calcula con count()/max()
 * sobre email_log_opens, no con un único opened_at que perdería el historial.
 *
 * El tracking nunca debe romper la vista del correo del destinatario: cualquier
 * fallo al registrar se traga y el gif se sirve igual.
 */
class EmailOpenTrackingController extends Controller
{
    private const TRANSPARENT_GIF = "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xFF\xFF\xFF!\xF9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;";

    public function pixel(Request $request, EmailLog $emailLog): Response
    {
        try {
            EmailLogOpen::create([
                'email_log_id' => $emailLog->id,
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 512),
                'opened_at' => now(),
            ]);
        } catch (Throwable) {
            // Silencioso a propósito: nunca debe fallar la carga del correo.
        }

        return response(self::TRANSPARENT_GIF, 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => strlen(self::TRANSPARENT_GIF),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
