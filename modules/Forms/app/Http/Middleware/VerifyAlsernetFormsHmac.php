<?php

namespace Modules\Forms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Forms\Support\HmacSigner;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mismo esquema que VerifyAlsernetHmac (HelpdeskPrestashop), con su propio
 * secreto de config (forms.webhook_secret, debe coincidir con
 * Configuration::get('ALSERNETFORMS_WEBHOOK_SECRET') del lado PrestaShop).
 */
class VerifyAlsernetFormsHmac
{
    private const REPLAY_CACHE_PREFIX = 'forms:webhook:nonce:';

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('forms.webhook_secret', '');

        if ($secret === '') {
            return response()->json(['ok' => false, 'error' => 'not configured'], 503);
        }

        $timestamp = (int) $request->header('X-Alsernet-Timestamp', 0);
        $signature = (string) $request->header('X-Alsernet-Signature', '');
        $rawBody = $request->getContent();

        if (! HmacSigner::verify($secret, $timestamp, $rawBody, $signature)) {
            Log::warning('Forms: HMAC verification failed.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['ok' => false, 'error' => 'invalid signature'], 401);
        }

        if ($this->isReplay($request, $signature)) {
            Log::warning('Forms: webhook replay rejected.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['ok' => false, 'error' => 'replay detected'], 401);
        }

        return $next($request);
    }

    /**
     * El nonce anti-replay es el hash de la FIRMA, y solo ese: cubre
     * timestamp+body y no es falsificable sin el secreto, así que una
     * petición reenviada tal cual se rechaza aquí.
     *
     * X-Alsernet-Idempotency-Key NO se deduplica en este middleware, aunque
     * antes sí lo hacía. Un reintento legítimo del cron de alsernetforms
     * (timeout de red, por ejemplo) llega con la MISMA idempotency key pero
     * con firma distinta, y por tanto no es un replay: es la situación para
     * la que existe la clave. Rechazarlo aquí con 401 dejaba inalcanzable la
     * deduplicación de FormSubmissionReceiverController, que responde 200 con
     * `deduplicated: true` y el ticket_number original — y como 401 es un
     * error, el cron seguía reintentando hasta agotar los intentos en vez de
     * darse por satisfecho.
     */
    private function isReplay(Request $request, string $signature): bool
    {
        return ! Cache::add(
            self::REPLAY_CACHE_PREFIX.hash('sha256', $signature),
            1,
            HmacSigner::TIMESTAMP_TOLERANCE_SECONDS
        );
    }
}
