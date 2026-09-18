<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\MacroExecutor;

class MacroApplyController extends Controller
{
    public function __construct(
        private readonly MacroExecutor $executor
    ) {
        $this->middleware('can:helpdesk.tickets.update');
    }

    /**
     * Acciones de una macro traducidas a frases.
     *
     * `actions` es JSON libre y en la base hay filas con formatos rotos
     * (strings sueltos, objetos sin envolver en array, tipos inexistentes):
     * se ignora en silencio lo que no se entiende en vez de romper la lista.
     *
     * @param  mixed  $actions
     * @return array<int, string>
     */
    private function describeActions($actions): array
    {
        if (! is_array($actions)) {
            return [];
        }

        $statuses = TicketStatus::query()->pluck('name', 'id');

        $out = [];
        foreach ($actions as $action) {
            if (! is_array($action) || ! isset($action['type'])) {
                continue;
            }

            $value = $action['value'] ?? null;

            $out[] = match ($action['type']) {
                'reply' => 'Responder al cliente con una plantilla',
                'internal_note' => 'Dejar una nota interna',
                'set_status' => 'Cambiar el estado a '.($statuses[$value] ?? $value),
                'set_priority' => 'Poner la prioridad en '.$value,
                'add_tag' => 'Añadir la etiqueta '.$value,
                'remove_tag' => 'Quitar la etiqueta '.$value,
                'assign' => 'Asignar el ticket',
                'close' => 'Cerrar el ticket',
                default => null,
            };
        }

        return array_values(array_filter($out));
    }

    public function list(): JsonResponse
    {
        $macros = Macro::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_shared', true)
                    ->orWhere('user_id', auth()->id());
            })
            ->select('id', 'name', 'description', 'actions')
            ->orderBy('name')
            ->get()
            ->map(fn (Macro $macro): array => [
                'id' => $macro->id,
                'name' => $macro->name,
                'description' => $macro->description,
                // Qué hace de verdad, en palabras. El modal "Macros y atajos"
                // lo enseña antes de aplicar: una macro que cierra el ticket y
                // otra que solo responde se leen igual por el nombre.
                'effects' => $this->describeActions($macro->actions),
            ]);

        return response()->json(['macros' => $macros]);
    }

    public function apply(Ticket $ticket, Macro $macro): JsonResponse
    {
        $this->authorize('update', $ticket);
        $this->authorize('apply', $macro);

        $this->executor->run($macro, $ticket);

        return response()->json([
            'success' => true,
            'message' => 'Macro aplicada: '.$macro->name,
        ]);
    }
}
