<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\OpslogBridgeLogRequest;
use Modules\HelpdeskPrestashop\Services\Ext\OpslogBridgeLogService;

/**
 * Pantalla "Registro del puente" (pieza 37): llamadas del panel al puente en
 * la ventana elegida, latencia, fallos, estado de la cola de webhooks y las
 * dos operaciones (reintentar webhooks muertos, calentar caché).
 *
 * La página se pinta vacía y los datos llegan por data(): si el puente no
 * responde, la pantalla sigue abriéndose y lo dice, en vez de dar un 500.
 */
class OpslogBridgeLogController extends Controller
{
    public function __construct(
        private readonly OpslogBridgeLogService $service
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('helpdeskprestashop::ext.opslog.bridge-log', [
            'canRetry' => $user->can('helpdeskprestashop.ops.webhooks.retry'),
            'canWarm' => $user->can('helpdeskprestashop.ops.cache.warm'),
            'lastWarmAt' => $this->service->lastWarmAt(),
            'windows' => OpslogBridgeLogRequest::WINDOWS,
        ]);
    }

    public function data(OpslogBridgeLogRequest $request): JsonResponse
    {
        try {
            $data = $this->service->read($request->hours(), $request->result());
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'El puente de PrestaShop no responde ahora mismo.'], 503);
        }

        if (! is_array($data)) {
            return response()->json([
                'success' => false,
                'message' => 'El puente no devuelve su registro. Comprueba que la extensión opslog está instalada en PrestaShop.',
            ], 502);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function requeue(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->can('helpdeskprestashop.ops.webhooks.retry')) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para reintentar webhooks.'], 403);
        }

        // La pantalla manda una clave por pulsación (un reenvío de la misma
        // petición = una sola devolución a la cola). Se ata a la acción y al
        // usuario porque el puente guarda las claves en una tabla común a
        // todas las acciones: una clave ajena no debe devolver su respuesta.
        // Sin cabecera, doble clic en el mismo minuto = una sola devolución.
        $clientKey = mb_substr(trim((string) $request->header('Idempotency-Key', '')), 0, 128);
        $idempotencyKey = sha1(implode(':', [
            'opslog.requeue_dead', $user->getAuthIdentifier(), $clientKey !== '' ? $clientKey : now()->format('YmdHi'),
        ]));

        try {
            $result = $this->service->requeueDead($idempotencyKey);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'El puente de PrestaShop no responde ahora mismo.'], 503);
        }

        if (! is_array($result)) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha podido reintentar los webhooks.'], 422);
        }

        if (function_exists('activity')) {
            activity('helpdeskprestashop')
                ->causedBy($user)
                ->withProperties([
                    'requeued' => (int) ($result['requeued'] ?? 0),
                    'skipped' => (int) ($result['skipped'] ?? 0),
                    'events' => $result['events'] ?? [],
                ])
                ->log('ps.ops.webhooks_requeued');
        }

        $requeued = (int) ($result['requeued'] ?? 0);

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => $requeued === 0
                ? 'No había webhooks fallidos que reintentar.'
                : ($requeued === 1 ? '1 webhook vuelve a la cola.' : $requeued.' webhooks vuelven a la cola.'),
        ]);
    }

    public function warmCache(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->can('helpdeskprestashop.ops.cache.warm')) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para calentar la caché.'], 403);
        }

        if (! $this->service->queueWarmCache()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya hay un calentado en marcha. Vuelve a intentarlo en unos minutos.',
            ], 429);
        }

        if (function_exists('activity')) {
            activity('helpdeskprestashop')
                ->causedBy($user)
                ->withProperties(['limit' => (int) config('helpdeskprestashop.ext.opslog.bridge_log.warm_limit', 200)])
                ->log('ps.ops.cache_warm_queued');
        }

        return response()->json([
            'success' => true,
            'message' => 'Calentado en cola: se precargan los clientes de las conversaciones y tickets abiertos.',
        ]);
    }
}
