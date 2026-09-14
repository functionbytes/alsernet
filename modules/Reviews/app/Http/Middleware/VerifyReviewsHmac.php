<?php

namespace Modules\Reviews\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Support\HmacSigner;
use Symfony\Component\HttpFoundation\Response;

/**
 * Comprueba la firma de lo que manda la tienda.
 *
 * Calcado del middleware de HelpdeskPrestashop, incluida la protección contra
 * reenvíos: una petición firmada solo vale una vez dentro de la ventana de
 * tolerancia del sello de tiempo.
 */
class VerifyReviewsHmac
{
    private const REPLAY_CACHE_PREFIX = 'reviews:webhook:nonce:';

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('reviews.secret', '');

        if ($secret === '') {
            return response()->json(['ok' => false, 'error' => 'not configured'], 503);
        }

        $timestamp = (int) $request->header('X-Alsernet-Timestamp', 0);
        $signature = (string) $request->header('X-Alsernet-Signature', '');

        if (! HmacSigner::verify($secret, $timestamp, $request->getContent(), $signature)) {
            Log::warning('Reviews: firma HMAC no válida.', [
                'ip' => $request->ip(),
                'event' => $request->header('X-Alsernet-Event'),
            ]);

            return response()->json(['ok' => false, 'error' => 'invalid signature'], 401);
        }

        // La firma cubre sello y cuerpo, así que es única por petición y sirve
        // de nonce. Cache::add() es atómico: si ya existe, es un reenvío.
        if (! Cache::add(self::REPLAY_CACHE_PREFIX.hash('sha256', $signature), 1, HmacSigner::TIMESTAMP_TOLERANCE_SECONDS)) {
            Log::warning('Reviews: reenvío rechazado.', ['ip' => $request->ip()]);

            return response()->json(['ok' => false, 'error' => 'replay detected'], 401);
        }

        return $next($request);
    }
}
