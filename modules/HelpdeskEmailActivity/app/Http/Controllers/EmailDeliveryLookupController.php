<?php

namespace Modules\HelpdeskEmailActivity\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Services\EmailDeliveryLookupService;
use Throwable;

/**
 * API de consulta de entregabilidad, pensada para que la usen otros módulos.
 *
 * El caso de uso: desde la ficha de un cliente (o de un destinatario de
 * campaña, o de un documento) responder "¿se le envió?, ¿lo abrió?" sin tener
 * que abrir el log de correo y buscar a mano.
 *
 * Devuelve JSON y se autoriza con el mismo permiso que el visor
 * (helpdeskemailactivity.view): quien puede ver el log puede consultarlo.
 */
class EmailDeliveryLookupController extends Controller
{
    public function __construct(
        private readonly EmailDeliveryLookupService $lookup,
    ) {}

    /**
     * GET /panel/helpdeskemailactivity/lookup/recipient?email=…&module=…&days=…&history=1
     *
     * Estado agregado de una dirección. Con history=1 añade los últimos
     * correos que se le mandaron.
     */
    public function recipient(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmailLog::class);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'module' => ['nullable', 'string', 'max:100'],
            'days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'history' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $summary = $this->lookup->forRecipient(
            $data['email'],
            $data['module'] ?? null,
            $data['days'] ?? null,
        );

        if ($request->boolean('history')) {
            $summary['history'] = $this->lookup->historyForRecipient(
                $data['email'],
                $data['module'] ?? null,
                $data['days'] ?? null,
                (int) ($data['limit'] ?? 50),
            );
        }

        return response()->json(['success' => true, 'data' => $summary]);
    }

    /**
     * POST /panel/helpdeskemailactivity/lookup/recipients
     *
     * La versión en lote, para que un listado pinte la columna "abierto" de
     * todas sus filas con una sola llamada en vez de una por fila.
     */
    public function recipients(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmailLog::class);

        $data = $request->validate([
            'emails' => ['required', 'array', 'min:1', 'max:500'],
            'emails.*' => ['required', 'email', 'max:255'],
            'module' => ['nullable', 'string', 'max:100'],
            'days' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->lookup->forRecipients(
                $data['emails'],
                $data['module'] ?? null,
                $data['days'] ?? null,
            ),
        ]);
    }

    /**
     * GET /panel/helpdeskemailactivity/lookup/entity?entity_type=…&entity_id=…
     *
     * Estado de los correos de una entidad: un ticket, un documento, un
     * destinatario de campaña. `entity_type` es el FQCN del modelo, tal como
     * lo guarda TracksEmailLog.
     */
    public function entity(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmailLog::class);

        $data = $request->validate([
            'entity_type' => ['required', 'string', 'max:255'],
            'entity_id' => ['required'],
            'module' => ['nullable', 'string', 'max:100'],
            'days' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->lookup->forEntity(
                $data['entity_type'],
                $data['entity_id'],
                $data['module'] ?? null,
                $data['days'] ?? null,
            ),
        ]);
    }

    /**
     * GET /panel/helpdeskemailactivity/lookup/module/{emailModule}/stats?from=&to=
     *
     * Agregados de un módulo (enviados, abiertos, clics, rebotes y sus tasas)
     * para alimentar el panel de estadísticas de ese módulo.
     */
    public function moduleStats(Request $request, string $emailModule): JsonResponse
    {
        $this->authorize('viewAny', EmailLog::class);

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        try {
            $from = isset($data['from']) ? CarbonImmutable::parse($data['from'])->startOfDay() : null;
            $to = isset($data['to']) ? CarbonImmutable::parse($data['to'])->endOfDay() : null;
        } catch (Throwable) {
            return response()->json(['success' => false, 'message' => 'Fechas inválidas.'], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $this->lookup->statsForModule($emailModule, $from, $to),
        ]);
    }

    /**
     * GET /panel/helpdeskemailactivity/lookup/modules
     *
     * Qué módulos han registrado correos. Útil para poblar un desplegable sin
     * que el consumidor tenga que saberse la lista.
     */
    public function modules(): JsonResponse
    {
        $this->authorize('viewAny', EmailLog::class);

        $modules = EmailLog::query()
            ->whereNotNull('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        return response()->json(['success' => true, 'data' => $modules]);
    }
}
