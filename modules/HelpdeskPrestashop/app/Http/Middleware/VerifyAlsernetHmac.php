<?php

namespace Modules\HelpdeskPrestashop\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Support\HmacSigner;
use Symfony\Component\HttpFoundation\Response;

class VerifyAlsernetHmac
{
    private const REPLAY_CACHE_PREFIX = 'helpdeskprestashop:webhook:nonce:';

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('helpdeskprestashop.webhook_secret', '');

        if ($secret === '') {
            return response()->json(['ok' => false, 'error' => 'not configured'], 503);
        }

        $timestamp = (int) $request->header('X-Alsernet-Timestamp', 0);
        $signature = (string) $request->header('X-Alsernet-Signature', '');
        $rawBody = $request->getContent();

        if (! HmacSigner::verify($secret, $timestamp, $rawBody, $signature)) {
            Log::warning('HelpdeskPrestashop: HMAC verification failed.', [
                'ip' => $request->ip(),
                'event' => $request->header('X-Alsernet-Event'),
            ]);

            return response()->json(['ok' => false, 'error' => 'invalid signature'], 401);
        }

        if ($this->isReplay($signature)) {
            Log::warning('HelpdeskPrestashop: webhook replay rejected.', [
                'ip' => $request->ip(),
                'event' => $request->header('X-Alsernet-Event'),
            ]);

            return response()->json(['ok' => false, 'error' => 'replay detected'], 401);
        }

        return $next($request);
    }

    /**
     * Anti-replay: una petición firmada solo puede aceptarse una vez dentro de
     * la ventana de tolerancia del timestamp. Se usa el hash de la firma como
     * nonce (la firma cubre timestamp+body, así que es única por petición y no
     * falsificable sin el secreto). Cache::add() es atómico: si la clave ya
     * existe, la petición es un replay.
     *
     * La deduplicación por X-Alsernet-Idempotency-Key (semántica de negocio:
     * "esta acción ya se procesó") vive en el controller (PsEventReceiverController),
     * que la marca 'done' solo tras éxito y libera el lock si el procesamiento
     * falla. Hacerla también aquí, de forma incondicional al llegar la firma,
     * quemaba la clave aunque el controller fallara después — un reintento
     * legítimo del emisor tras un error 500 se rechazaba como "replay" sin
     * haberse procesado nunca.
     */
    private function isReplay(string $signature): bool
    {
        return ! Cache::add(
            self::REPLAY_CACHE_PREFIX.hash('sha256', $signature),
            1,
            HmacSigner::TIMESTAMP_TOLERANCE_SECONDS
        );
    }
}
