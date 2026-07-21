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

        if ($this->isReplay($request, $signature)) {
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
     * La cabecera X-Alsernet-Idempotency-Key (que el emisor envía en
     * escrituras) NO puede sustituir a la firma como nonce mientras no esté
     * cubierta por el HMAC: un atacante podría reenviar la misma petición
     * firmada cambiando solo esa cabecera. Por eso, cuando llega, se deduplica
     * ADEMÁS de (nunca en lugar de) el hash de la firma.
     */
    private function isReplay(Request $request, string $signature): bool
    {
        $ttl = HmacSigner::TIMESTAMP_TOLERANCE_SECONDS;

        if (! Cache::add(self::REPLAY_CACHE_PREFIX.hash('sha256', $signature), 1, $ttl)) {
            return true;
        }

        $idempotencyKey = (string) $request->header('X-Alsernet-Idempotency-Key', '');

        if ($idempotencyKey !== ''
            && ! Cache::add(self::REPLAY_CACHE_PREFIX.'idem:'.hash('sha256', $idempotencyKey), 1, $ttl)) {
            return true;
        }

        return false;
    }
}
