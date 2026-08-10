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
     * Ver el mismo razonamiento en VerifyAlsernetHmac: el hash de la firma es
     * el nonce real (cubre timestamp+body, no falsificable sin el secreto);
     * X-Alsernet-Idempotency-Key se deduplica ADEMÁS, nunca en su lugar.
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
