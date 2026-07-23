<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskTickets\Events\TicketViewing;
use Modules\HelpdeskTickets\Models\Ticket;
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

    public function leave(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        $this->presence->leave($ticket->id, $user->id, now()->timestamp);

        TicketViewing::dispatch($ticket->id, $user->id, $this->displayName($user), 'left');

        return response()->json(['success' => true]);
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
