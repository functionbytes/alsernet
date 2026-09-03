<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskTickets\Http\Requests\StoreTicketFollowupRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketFollowup;

/**
 * Recordatorios de seguimiento de un ticket: el agente programa "revisar este
 * ticket el día X"; un comando programado (ticket:send-followups) los notifica
 * al vencer. Hasta ahora la tabla/modelo existían sin punto de entrada.
 */
class TicketFollowupsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.update');
    }

    public function store(StoreTicketFollowupRequest $request, Ticket $ticket): JsonResponse
    {
        $datos = $request->validated();

        // Una secuencia de pasos o, si no viene, el recordatorio suelto de
        // siempre — que es un paso único, para no tener dos caminos distintos.
        $pasos = $datos['steps'] ?? [[
            'scheduled_at' => $datos['scheduled_at'],
            'note' => $datos['note'] ?? null,
        ]];

        $cancelar = $request->boolean('cancel_if_customer_replies', true);

        // Los pasos se guardan en orden cronológico: `step` es el número que
        // ve el agente ("paso 2 de 3"), así que ordenar por fecha evita que
        // dependa de cómo los tecleó.
        $pasos = collect($pasos)->sortBy('scheduled_at')->values();

        $creados = $pasos->map(fn (array $paso, int $i) => $ticket->followups()->create([
            'user_id' => $request->user()->id,
            'scheduled_at' => $paso['scheduled_at'],
            'note' => $paso['note'] ?? null,
            'step' => $i + 1,
            'cancel_if_customer_replies' => $cancelar,
            'is_sent' => false,
        ]));

        return response()->json([
            'success' => true,
            'message' => $creados->count() === 1
                ? 'Seguimiento programado.'
                : 'Secuencia de '.$creados->count().' pasos programada.',
            'data' => $creados->map(fn ($f) => [
                'id' => $f->id,
                'step' => $f->step,
                'scheduled_at' => $f->scheduled_at?->toIso8601String(),
                'note' => $f->note,
            ])->all(),
        ], 201);
    }

    /**
     * Cancela la secuencia entera de este ticket (los pasos que aún no se han
     * enviado). Antes solo se podía quitar paso a paso.
     */
    public function destroyAll(Ticket $ticket): JsonResponse
    {
        $cancelados = $ticket->followups()->pending()->update(['cancelled_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => $cancelados === 1
                ? 'Seguimiento cancelado.'
                : $cancelados.' pasos cancelados.',
        ]);
    }

    public function destroy(Ticket $ticket, TicketFollowup $followup): JsonResponse
    {
        abort_unless($followup->ticket_id === $ticket->id, 404);

        $followup->delete();

        return response()->json(['success' => true, 'message' => 'Seguimiento cancelado.']);
    }
}
