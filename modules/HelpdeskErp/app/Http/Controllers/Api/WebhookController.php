<?php

namespace Modules\HelpdeskErp\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskErp\Events\ErpOrdersReady;
use Modules\HelpdeskErp\Services\CustomerTimelineService;
use Modules\HelpdeskErp\Services\ErpContextService;

class WebhookController extends Controller
{
    /** Ventana de validez del timestamp firmado (segundos). */
    private const TIMESTAMP_TOLERANCE_SECONDS = 300;

    private const REPLAY_CACHE_PREFIX = 'helpdeskerp:webhook:nonce:';

    public function __construct(
        private readonly ErpContextService $service,
        private readonly CustomerTimelineService $timelineService,
    ) {}

    /**
     * POST /api/helpdeskErp/webhooks/orders-ready
     *
     * Called by the manager when the Oracle background scan finishes and orders
     * for a customer are ready. Invalidates cache and broadcasts an event via Reverb.
     *
     * @OA\Post(
     *     path="/api/helpdeskErp/webhooks/orders-ready",
     *     summary="Webhook: notifica que el escaneo Oracle de pedidos de un cliente terminó",
     *     tags={"HelpdeskWebhooks"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="cliente@ejemplo.com"),
     *             @OA\Property(property="customer_id", type="integer", nullable=true, example=12345)
     *         )
     *     ),
     *
     *     @OA\Parameter(name="X-Erp-Signature", in="header", required=true, description="HMAC-SHA256 del timestamp:body", @OA\Schema(type="string")),
     *     @OA\Parameter(name="X-Erp-Timestamp", in="header", required=true, description="Unix timestamp (válido ±5 min)", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Webhook procesado", @OA\JsonContent(@OA\Property(property="ok", type="boolean", example=true))),
     *     @OA\Response(response=401, description="Firma inválida o timestamp expirado"),
     *     @OA\Response(response=422, description="Email inválido"),
     *     @OA\Response(response=503, description="Webhook no configurado")
     * )
     */
    public function ordersReady(Request $request): JsonResponse
    {
        $secret = (string) config('helpdeskErp.webhook_secret', '');

        if ($secret === '') {
            static $logged = false;
            if (! $logged) {
                $logged = true;
                Log::warning('HelpdeskErp webhook: ERP_WEBHOOK_SECRET not configured.');
            }

            return response()->json(['ok' => false, 'error' => 'webhook not configured'], 503);
        }

        $timestamp = (int) $request->header('X-Erp-Timestamp', 0);

        if ($timestamp === 0 || abs(time() - $timestamp) > self::TIMESTAMP_TOLERANCE_SECONDS) {
            return response()->json(['ok' => false, 'error' => 'invalid timestamp'], 401);
        }

        $rawBody = $request->getContent();
        $expected = hash_hmac('sha256', $timestamp.':'.$rawBody, $secret);
        $signature = (string) $request->header('X-Erp-Signature', '');

        if (! hash_equals($expected, $signature)) {
            Log::warning('HelpdeskErp webhook: invalid HMAC signature.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['ok' => false, 'error' => 'invalid signature'], 401);
        }

        // Anti-replay (mismo patrón que VerifyAlsernetHmac en HelpdeskPrestashop):
        // una petición firmada solo puede aceptarse una vez dentro de la ventana
        // del timestamp. El hash de la firma actúa como nonce — cubre
        // timestamp+body, es única por petición y no falsificable sin el
        // secreto. Cache::add() es atómico: si la clave ya existe, es un replay.
        // TTL = ventana de tolerancia: cuando el nonce expira, la firma ya no
        // pasa el check de timestamp.
        $nonceKey = self::REPLAY_CACHE_PREFIX.hash('sha256', $signature);

        if (! Cache::add($nonceKey, 1, self::TIMESTAMP_TOLERANCE_SECONDS)) {
            Log::warning('HelpdeskErp webhook: replay rejected.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['ok' => false, 'error' => 'replay detected'], 401);
        }

        if (! helpdesk_erp_enabled()) {
            // Integration disabled: acknowledge with 204 so the sender does not retry indefinitely.
            return response()->json(null, 204);
        }

        $email = trim((string) $request->input('email', ''));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['ok' => false, 'error' => 'invalid email'], 422);
        }

        $customerId = $request->input('customer_id');

        $this->service->forgetCache($email);
        $this->timelineService->forgetCache($email);

        broadcast(new ErpOrdersReady($email, $customerId !== null ? (int) $customerId : null));

        return response()->json(['ok' => true]);
    }
}
