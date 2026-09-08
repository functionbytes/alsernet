<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskTickets\Http\Requests\PreviewTicketOpsAutomationRequest;
use Modules\HelpdeskTickets\Http\Requests\StoreTicketOpsAutomationRequest;
use Modules\HelpdeskTickets\Models\Automation;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\AutomationEngine;
use Modules\HelpdeskTickets\Support\AutomationCatalog;

/**
 * Reglas de escalado desde el modal de la pantalla de tickets (pill
 * "Escalado", modal 29 del mockup).
 *
 * Existe aparte de Settings\AutomationsController a propósito: aquél devuelve
 * vistas Blade y redirecciones (formulario de Ajustes, con las condiciones y
 * acciones tecleadas como JSON), y este habla JSON con el modal. No se toca
 * el otro ni sus rutas.
 */
class TicketOpsAutomationsController extends Controller
{
    /**
     * Listado de reglas + catálogo de lo que el motor sabe ejecutar.
     *
     * Solo pide permiso de lectura de tickets, como el settings-snapshot que
     * alimentaba antes la lista del modal: ver qué reglas hay no es
     * administrar. `can_manage` le dice al modal si puede enseñar el editor.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $rules = Automation::query()
            ->ticketDomain()
            ->orderBy('order')
            ->orderBy('id')
            ->limit(50)
            ->get(['id', 'name', 'trigger_event', 'conditions', 'actions', 'is_active', 'run_count', 'last_run_at']);

        return response()->json([
            'can_manage' => (bool) $request->user()?->can('helpdesk.tickets.settings'),
            'rules' => $rules->map(fn (Automation $a) => $this->toPayload($a))->all(),
            'catalog' => [
                'triggers' => AutomationCatalog::triggers(),
                'operators' => AutomationCatalog::operators(),
                'fields' => AutomationCatalog::fields(),
                'actions' => AutomationCatalog::actions(),
                'priorities' => AutomationCatalog::priorities(),
            ],
        ]);
    }

    public function store(StoreTicketOpsAutomationRequest $request): JsonResponse
    {
        $automation = Automation::create([
            'name' => $request->validated('name'),
            'trigger_event' => $request->validated('trigger_event'),
            'conditions' => $request->condicionesNormalizadas(),
            'actions' => $request->accionesNormalizadas(),
            'is_active' => $request->boolean('is_active', true),
            // Al final de la cola: una regla nueva no debe adelantar a las que
            // ya estaban ordenadas a mano en Ajustes.
            'order' => ((int) Automation::query()->max('order')) + 1,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Regla creada.',
            'rule' => $this->toPayload($automation),
        ], 201);
    }

    /**
     * Activar/pausar sin salir del modal.
     */
    public function toggle(Request $request, Automation $automation): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);
        abort_unless($request->user()?->can('helpdesk.tickets.settings'), 403);
        // El route-model-binding no filtra por dominio: sin esto se podía
        // pausar/activar una regla de Conversaciones (misma tabla física)
        // tecleando su id en la URL de este endpoint de Tickets.
        abort_unless(array_key_exists($automation->trigger_event, Automation::$triggerEvents), 404);

        $automation->update(['is_active' => ! $automation->is_active]);

        return response()->json([
            'success' => true,
            'message' => $automation->is_active ? 'Regla activada.' : 'Regla pausada.',
            'rule' => $this->toPayload($automation->refresh()),
        ]);
    }

    /**
     * "Probar regla": prueba en seco de las condiciones contra los últimos
     * tickets. No ejecuta ninguna acción ni guarda nada.
     *
     * El disparador no entra en la prueba porque es un evento (ticket creado,
     * asignado…): no se puede reproducir sobre tickets que ya existen. Lo que
     * responde —cuántos de los últimos N tickets cumplirían las condiciones—
     * es justo lo que hace falta para saber si una regla es demasiado amplia
     * antes de activarla.
     */
    public function preview(PreviewTicketOpsAutomationRequest $request, AutomationEngine $engine): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);
        abort_unless($request->user()?->can('helpdesk.tickets.settings'), 403);

        $conditions = $request->condicionesNormalizadas();
        $scanned = 200;

        $tickets = Ticket::query()
            ->orderByDesc('id')
            ->limit($scanned)
            ->get(['id', 'ticket_number', 'subject', 'priority', 'status_id', 'group_id', 'assignee_id',
                'category_id', 'escalation_count', 'sla_first_response_breached',
                'sla_next_response_breached', 'sla_resolution_breached']);

        $matches = $tickets->filter(fn (Ticket $t) => $engine->matchesConditions($conditions, $t));

        return response()->json([
            'success' => true,
            'scanned' => $tickets->count(),
            'matched' => $matches->count(),
            'sample' => $matches->take(3)->map(fn (Ticket $t) => [
                'ticket_number' => $t->ticket_number,
                'subject' => $t->subject,
            ])->values()->all(),
        ]);
    }

    /**
     * Una regla tal y como la pinta el modal. Las condiciones y acciones van
     * en crudo: los nombres de estado/equipo/agente los resuelve el JS con
     * los catálogos que la pantalla ya tiene cargados, sin una consulta por
     * regla.
     *
     * @return array<string, mixed>
     */
    private function toPayload(Automation $automation): array
    {
        return [
            'id' => $automation->id,
            'name' => $automation->name,
            'trigger_event' => $automation->trigger_event,
            'is_active' => (bool) $automation->is_active,
            'run_count' => (int) $automation->run_count,
            'last_run_at_human' => $automation->last_run_at?->diffForHumans(),
            'conditions' => $automation->conditions ?? [],
            'actions' => $automation->actions ?? [],
        ];
    }
}
