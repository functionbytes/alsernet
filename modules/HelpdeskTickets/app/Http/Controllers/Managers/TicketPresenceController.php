<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskTickets\Events\TicketViewing;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Notifications\TicketCollisionNudge;
use Modules\HelpdeskTickets\Services\TicketPresenceService;

/**
 * Detección de colisión de agentes: el panel del ticket late (heartbeat) contra
 * este endpoint mientras está abierto; devuelve los otros agentes presentes para
 * pintar el aviso "Fulano está viendo/respondiendo este ticket".
 */
class TicketPresenceController extends Controller
{
    public function __construct(private readonly TicketPresenceService $presence)
    {
        $this->middleware('can:helpdesk.tickets.view');
    }

    public function heartbeat(Request $request, Ticket $ticket): JsonResponse
    {
        $action = $request->input('action') === 'replying' ? 'replying' : 'viewing';
        $user = $request->user();
        $name = $this->displayName($user);

        $others = $this->presence->heartbeat(
            $ticket->id,
            $user->id,
            $name,
            $action,
            now()->timestamp,
        );

        TicketViewing::dispatch($ticket->id, $user->id, $name, $action);

        return response()->json(['success' => true, 'data' => ['viewers' => $others]]);
    }

    /**
     * Presencia de VARIOS tickets a la vez, para el listado — heartbeat()/
     * leave() son por ticket abierto (el detalle), esto es de solo lectura
     * y no requiere tener ninguno abierto. `ids` es una lista separada por
     * comas de los tickets que la fila tiene cargados en ese momento
     * (nunca todos los de la bandeja, solo la página visible).
     */
    public function overview(Request $request): JsonResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return response()->json(['success' => true, 'data' => []]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->presence->viewersForMany($ids, now()->timestamp),
        ]);
    }

    public function leave(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        $this->presence->leave($ticket->id, $user->id, now()->timestamp);

        TicketViewing::dispatch($ticket->id, $user->id, $this->displayName($user), 'left');

        return response()->json(['success' => true]);
    }

    /**
     * Modal 23 "Bandeja compartida": "Avisar a X" cuando dos agentes
     * coinciden en el mismo ticket. Es un aviso puntual (notificación), no
     * una asignación ni un mensaje del hilo -- el otro agente decide si
     * cede el ticket o sigue. Cache::add() con TTL corto evita el spam de
     * pulsar el botón varias veces seguidas al mismo agente.
     */
    public function nudge(Request $request, Ticket $ticket): JsonResponse
    {
        $to = User::find((int) $request->input('to_user_id'));
        $from = $request->user();

        if (! $to) {
            return response()->json(['success' => false, 'message' => 'Agente no encontrado.'], 404);
        }

        if ($to->is($from)) {
            return response()->json(['success' => false, 'message' => 'No puedes avisarte a ti mismo.'], 422);
        }

        // Mismo criterio que para verte el ticket: no tiene sentido avisar a
        // alguien que ni siquiera podría abrirlo.
        if (! $to->can('view', $ticket)) {
            return response()->json(['success' => false, 'message' => 'Ese agente no tiene acceso a este ticket.'], 422);
        }

        $key = "helpdesk:ticket:{$ticket->id}:nudge:{$from->id}:{$to->id}";
        if (! Cache::add($key, true, 60)) {
            return response()->json(['success' => false, 'message' => 'Ya le has avisado hace un momento.'], 429);
        }

        $to->notify(new TicketCollisionNudge($ticket, $from));

        return response()->json(['success' => true, 'message' => 'Aviso enviado a '.$this->displayName($to).'.']);
    }

    /**
     * El modelo User no tiene columna `name`; el nombre completo es el accessor
     * `full_name` (firstname+lastname), que puede venir vacío para cuentas de
     * servicio. Garantiza una etiqueta no vacía para el aviso de colisión.
     */
    private function displayName(User $user): string
    {
        $name = trim((string) $user->full_name);

        return $name !== '' ? $name : 'Agente #'.$user->id;
    }
}
