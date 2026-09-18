<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Services\AutoAssignmentService;
use Modules\HelpdeskTickets\Http\Requests\Managers\UpdateWorkloadAssignmentRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\AssignmentService;

/**
 * Modal 45 "Carga de agentes" (ve-agent-load) del riel de operación.
 *
 * Va aparte de TicketOpsController a propósito: ese controlador ya reúne
 * salud de cola, CSAT, reputación y buzones, y este modal necesita cuatro
 * lecturas más (equipos, ajustes de reparto, desglose de lo sin asignar) que
 * no comparte con ninguno de ellos.
 *
 * Qué NO hay aquí, y por qué:
 *
 * - **Capacidad por agente**. El mockup pinta "87 %" y "capacidad por agente:
 *   12 abiertos". No existe ninguna capacidad de TICKETS por agente:
 *   `helpdesk_agent_settings.max_concurrent_conversations` es un límite de
 *   CONVERSACIONES y además su contador (`current_open_count`) no lo
 *   incrementa nadie —solo AgentPresenceService lo pone a cero—, así que hoy
 *   ni siquiera frena una asignación. Un porcentaje calculado contra eso sería
 *   inventado, y se omite.
 * - **Reasignar tickets entre agentes**. Es otra pantalla (el modal de
 *   asignación por ticket) y otro permiso.
 */
class TicketOpsWorkloadController extends Controller
{
    /**
     * Estrategias que el lado de TICKETS sabe ejecutar de verdad, con el mismo
     * criterio que el `match` de AutoAssignNewTicket/AutoAssignUnassignedTickets.
     * Si el módulo Helpdesk añade una estrategia nueva, aquí aparece marcada
     * como no soportada en vez de prometer un reparto que no ocurriría.
     */
    private const TICKET_STRATEGIES = [
        'round_robin' => [
            'label' => 'Turno rotatorio',
            'description' => 'Reparte por turnos entre los agentes disponibles con menos tickets abiertos.',
        ],
        'least_load' => [
            'label' => 'Por carga',
            'description' => 'Elige al agente disponible con menos carga, ponderando cada ticket por su prioridad.',
        ],
        'manual' => [
            'label' => 'Manual',
            'description' => 'No asigna nada de forma automática: los tickets nuevos entran sin agente.',
        ],
    ];

    /**
     * Todo lo que pinta el modal, en una sola llamada.
     *
     * Coste fijo en consultas, no proporcional al número de agentes: el
     * endpoint viejo (TicketOpsController::workload) llamaba a
     * AssignmentService::getAgentWorkload() dentro del map, es decir un
     * COUNT por agente — 72 consultas en este entorno. Aquí las cargas salen
     * de dos agregados agrupados por assignee_id.
     */
    public function overview(AssignmentService $assignment): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        // Pool que HOY recibiría reparto automático (rol, disponibilidad,
        // turnos, vacaciones y presencia). Se consume tal cual, sin tocar el
        // servicio.
        $eligible = $assignment->getAvailableAgents();
        $eligibleIds = $eligible->pluck('id')->all();

        $openByAgent = $this->openTicketsByAgent();
        $atRiskByAgent = $this->atRiskTicketsByAgent();

        // Un agente con tickets abiertos que ya NO está en el pool (de
        // vacaciones, ausente, o sin el rol) desaparecía del modal aunque
        // fuera el más cargado de todos. Se une a mano: la carga que tiene
        // sigue siendo carga real de la que alguien tiene que responder.
        $withLoad = $openByAgent->keys()->merge($atRiskByAgent->keys())->map(fn ($id) => (int) $id);
        $allIds = collect($eligibleIds)->merge($withLoad)->unique()->values();

        $names = $this->agentNames($eligible, $allIds->all());
        $teamsByAgent = $this->teamsByAgent($allIds->all());

        $agents = $allIds
            ->map(fn (int $id) => [
                'id' => $id,
                'name' => $names[$id] ?? ('#'.$id),
                'open_tickets' => (int) ($openByAgent[$id] ?? 0),
                'at_risk' => (int) ($atRiskByAgent[$id] ?? 0),
                // Falso = tiene carga pero el reparto automático ya no le
                // manda nada. Es la explicación de por qué su cifra no baja.
                'eligible' => in_array($id, $eligibleIds, true),
                'team_ids' => array_values($teamsByAgent['by_agent'][$id] ?? []),
            ])
            ->sortBy([
                fn ($a, $b) => $b['open_tickets'] <=> $a['open_tickets'],
                fn ($a, $b) => $b['at_risk'] <=> $a['at_risk'],
                fn ($a, $b) => strcasecmp($a['name'], $b['name']),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'agents' => $agents->all(),
            'teams' => $this->teamRows($teamsByAgent['groups'], $teamsByAgent['by_agent'], $agents),
            'unassigned' => $this->unassignedBreakdown(),
            'assignment' => $this->assignmentConfig($assignment),
            // Explícito, no ausente: el modal distingue "no hay capacidad
            // configurada" de "no me la han enviado".
            'capacity' => null,
            'can_manage' => (bool) auth()->user()?->can('helpdesk.tickets.settings'),
            'can_distribute' => (bool) auth()->user()?->can('helpdesk.tickets.update'),
        ]);
    }

    /**
     * Guarda el interruptor y la estrategia del reparto automático.
     *
     * POST y no PUT: un PUT real por AJAX devuelve 405 en este Docker aunque
     * la ruta exista (gotcha ya documentado en el proyecto).
     */
    public function updateAssignment(UpdateWorkloadAssignmentRequest $request, AutoAssignmentService $service): JsonResponse
    {
        $current = $service->config();

        // retry/fallback se reenvían tal cual: son ajustes de conversaciones y
        // saveConfig() los reescribe siempre, así que omitirlos los borraría.
        $service->saveConfig(
            $request->validated('strategy'),
            $current['retry'],
            $current['fallback'],
            $request->boolean('enabled'),
        );

        Setting::set('auto_assign.language_routing', $request->boolean('language_routing') ? '1' : '0', 'auto_assign');

        return response()->json([
            'success' => true,
            'message' => 'Reparto automático guardado.',
            'assignment' => $this->assignmentConfig(app(AssignmentService::class)),
        ]);
    }

    /**
     * Tickets abiertos por agente. `closed_at` nulo es el mismo criterio de
     * "abierto" que usa AssignmentService::getAgentWorkload(), para que la
     * cifra del modal y la que decide el reparto no puedan discrepar.
     *
     * @return Collection<int, int>
     */
    private function openTicketsByAgent(): Collection
    {
        return Ticket::query()
            ->whereNotNull('assignee_id')
            ->whereNull('closed_at')
            ->selectRaw('assignee_id, COUNT(*) as total')
            ->groupBy('assignee_id')
            ->pluck('total', 'assignee_id')
            ->mapWithKeys(fn ($total, $id) => [(int) $id => (int) $total]);
    }

    /**
     * "N en riesgo": SLA vencido o a punto de vencer, mismo criterio que el
     * endpoint anterior PERO solo sobre tickets abiertos. Las banderas
     * `sla_*_breached` no se limpian al cerrar, así que sin ese filtro un
     * agente arrastraba de por vida los incumplimientos de tickets ya
     * cerrados y su "en riesgo" nunca bajaba.
     *
     * @return Collection<int, int>
     */
    private function atRiskTicketsByAgent(): Collection
    {
        return Ticket::query()
            ->whereNotNull('assignee_id')
            ->whereNull('closed_at')
            ->where(fn ($q) => $q->slaWarning()->orWhere(fn ($q2) => $q2->slaBreach()))
            ->selectRaw('assignee_id, COUNT(*) as total')
            ->groupBy('assignee_id')
            ->pluck('total', 'assignee_id')
            ->mapWithKeys(fn ($total, $id) => [(int) $id => (int) $total]);
    }

    /**
     * Nombres de todos los agentes implicados con una sola consulta extra
     * (los del pool ya vienen hidratados). fullName() y no
     * firstname.' '.lastname: hay usuarios con el apellido repetido en el
     * nombre y saldrían como "Ángeles Ángeles".
     *
     * @param  Collection<int, User>  $eligible
     * @param  array<int, int>  $ids
     * @return array<int, string>
     */
    private function agentNames(Collection $eligible, array $ids): array
    {
        $names = $eligible->mapWithKeys(fn (User $u) => [(int) $u->id => $u->fullName()])->all();

        $missing = array_values(array_diff($ids, array_keys($names)));

        if ($missing !== []) {
            foreach (User::query()->whereIn('id', $missing)->get(['id', 'firstname', 'lastname']) as $user) {
                $names[(int) $user->id] = $user->fullName();
            }
        }

        return $names;
    }

    /**
     * Equipos reales: pivote `helpdesk_group_user` + `helpdesk_groups`, las
     * mismas tablas que usan Group::users() y el selector de grupo del CRUD.
     *
     * Se consultan en crudo (dos SELECT) en lugar de por la relación
     * belongsToMany, que es cruzada entre bases (grupos en `helpdesk`,
     * usuarios en la conexión por defecto) y obligaría a reescribir el `from`
     * a mano para acabar con los mismos datos.
     *
     * @param  array<int, int>  $agentIds
     * @return array{groups: array<int, array{id:int,name:string,is_active:bool}>, by_agent: array<int, array<int, int>>}
     */
    private function teamsByAgent(array $agentIds): array
    {
        if ($agentIds === []) {
            return ['groups' => [], 'by_agent' => []];
        }

        $connection = DB::connection('helpdesk');

        $memberships = $connection->table('helpdesk_group_user')
            ->whereIn('user_id', $agentIds)
            ->get(['group_id', 'user_id']);

        if ($memberships->isEmpty()) {
            return ['groups' => [], 'by_agent' => []];
        }

        $groups = $connection->table('helpdesk_groups')
            ->whereIn('id', $memberships->pluck('group_id')->unique()->all())
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'is_active'])
            ->mapWithKeys(fn ($g) => [(int) $g->id => [
                'id' => (int) $g->id,
                'name' => (string) $g->name,
                'is_active' => (bool) $g->is_active,
            ]])
            ->all();

        $byAgent = [];

        foreach ($memberships as $row) {
            $groupId = (int) $row->group_id;

            // Una pertenencia a un grupo borrado (soft delete) no cuenta como
            // equipo: el grupo ya no existe para el resto de la aplicación.
            if (! isset($groups[$groupId])) {
                continue;
            }

            $byAgent[(int) $row->user_id][] = $groupId;
        }

        return ['groups' => $groups, 'by_agent' => $byAgent];
    }

    /**
     * Carga agregada por equipo. Es la suma de la carga de sus miembros, no
     * `helpdesk_tickets.group_id`: esa columna existe pero está vacía en la
     * práctica (ningún ticket la trae), así que agrupar por ella daría todos
     * los equipos a cero.
     *
     * Los agentes sin equipo no se meten en un grupo inventado: el modal los
     * agrupa aparte a partir de `team_ids` vacío.
     *
     * @param  array<int, array{id:int,name:string,is_active:bool}>  $groups
     * @param  array<int, array<int, int>>  $byAgent
     * @param  Collection<int, array<string, mixed>>  $agents
     * @return array<int, array<string, mixed>>
     */
    private function teamRows(array $groups, array $byAgent, Collection $agents): array
    {
        $rows = [];

        foreach ($agents as $agent) {
            foreach ($byAgent[$agent['id']] ?? [] as $groupId) {
                $rows[$groupId] ??= [
                    'id' => $groups[$groupId]['id'],
                    'name' => $groups[$groupId]['name'],
                    'is_active' => $groups[$groupId]['is_active'],
                    'agents' => 0,
                    'open_tickets' => 0,
                    'at_risk' => 0,
                    'agent_ids' => [],
                ];

                $rows[$groupId]['agents']++;
                $rows[$groupId]['open_tickets'] += $agent['open_tickets'];
                $rows[$groupId]['at_risk'] += $agent['at_risk'];
                $rows[$groupId]['agent_ids'][] = $agent['id'];
            }
        }

        usort($rows, fn ($a, $b) => [$b['open_tickets'], $b['at_risk']] <=> [$a['open_tickets'], $a['at_risk']]);

        return array_values($rows);
    }

    /**
     * Desglose de lo que va a repartir el botón, para que la cifra del botón
     * no prometa más de lo que el reparto hace.
     *
     * `total` replica EXACTAMENTE la query de
     * TicketOpsController::distributeUnassigned() (sin asignar + no pospuesto)
     * porque es la que se va a ejecutar; `closed` y `with_category` son los
     * dos motivos por los que parte de ese total no acabará asignada.
     *
     * @return array<string, int>
     */
    private function unassignedBreakdown(): array
    {
        $base = fn () => Ticket::query()->whereNull('assignee_id')->notSnoozed();

        return [
            'total' => $base()->count(),
            'open' => $base()->whereNull('closed_at')->count(),
            'closed' => $base()->whereNotNull('closed_at')->count(),
            'with_category' => $base()->whereNull('closed_at')->whereNotNull('category_id')->count(),
            // El endpoint de reparto procesa como mucho 50 por pulsación.
            'per_run_limit' => 50,
        ];
    }

    /**
     * Ajustes reales del reparto automático. Son GLOBALES del helpdesk
     * (helpdesk_settings, grupo `auto_assign`): los mismos que edita el modal
     * de Conversaciones. El modal lo advierte antes de guardar.
     *
     * @return array<string, mixed>
     */
    private function assignmentConfig(AssignmentService $assignment): array
    {
        $core = app(AutoAssignmentService::class);
        $config = $core->config();

        return [
            'enabled' => (bool) $config['enabled'],
            'strategy' => (string) $config['strategy'],
            'language_routing' => $this->languageRoutingEnabled(),
            'strategies' => $this->strategyCatalogue(),
            // El reparto manual del botón llama siempre a
            // autoAssignByWorkload(), tenga la que tenga configurada la
            // estrategia global. El modal lo dice en vez de dar a entender
            // que respeta lo elegido arriba.
            'manual_distribution_strategy' => 'least_load',
            'category_pool_available' => $this->categoryPoolAvailable($assignment),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function strategyCatalogue(): array
    {
        return array_map(fn (string $value) => [
            'value' => $value,
            'label' => self::TICKET_STRATEGIES[$value]['label'] ?? $value,
            'description' => self::TICKET_STRATEGIES[$value]['description'] ?? '',
            'supported' => isset(self::TICKET_STRATEGIES[$value]),
        ], AutoAssignmentService::STRATEGIES);
    }

    /**
     * Toggle de ruteo por idioma (solo tickets). Misma lectura que
     * AssignmentService::languageRoutingEnabled(), que es privada.
     */
    private function languageRoutingEnabled(): bool
    {
        $stored = Setting::get('auto_assign.language_routing');

        if ($stored === null) {
            return (bool) config('helpdesk.auto_assignment.language_routing', false);
        }

        return filter_var($stored, FILTER_VALIDATE_BOOL);
    }

    /**
     * ¿Funciona el filtro por categoría del reparto?
     *
     * Las dos estrategias piden el pool con `getAvailableAgents($ticket->
     * category_id)`, que filtra por la relación `User::agentCategories()`.
     * Esa relación no existe en el modelo, así que la llamada lanza
     * BadMethodCallException, AssignmentService la traga en su try/catch y el
     * ticket se queda sin asignar SIN error visible: hoy ningún ticket con
     * categoría llega a repartirse.
     *
     * Se comprueba con una sonda en caliente y no dando el fallo por sentado:
     * el día que se añada la relación, la sonda pasa y el aviso desaparece
     * solo. Devuelve null cuando no hay ninguna categoría real que sondear.
     */
    private function categoryPoolAvailable(AssignmentService $assignment): ?bool
    {
        $categoryId = Ticket::query()
            ->whereNull('assignee_id')
            ->notSnoozed()
            ->whereNull('closed_at')
            ->whereNotNull('category_id')
            ->value('category_id');

        if (! $categoryId) {
            return null;
        }

        try {
            $assignment->getAvailableAgents((int) $categoryId);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
