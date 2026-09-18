<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\Helpdesk\Concerns\FormatsAgentNames;
use Modules\Helpdesk\Http\Requests\Managers\SlaBreachesReportDataRequest;
use Nwidart\Modules\Facades\Module;

/**
 * Manager report: SLA resolution breaches grouped by agent, plus upcoming
 * breaches in the next 24 hours.
 *
 * HelpdeskTickets is an optional module: when it is disabled the data endpoint
 * returns { available: false } and the view renders an unavailable state. The
 * SlaService and Ticket symbols are resolved through string-literal FQCNs
 * behind a Module::find() + class_exists() guard so Helpdesk never hard-depends
 * on HelpdeskTickets.
 */
class SlaBreachesReportController extends Controller
{
    use FormatsAgentNames;

    private const SLA_SERVICE = 'Modules\\HelpdeskTickets\\Services\\SlaService';

    private const CATALOG_SERVICE = 'Modules\\HelpdeskTickets\\Services\\CatalogCacheService';

    public function __construct()
    {
        $this->middleware('can:helpdesk.reports.view');
    }

    /**
     * GET /panel/helpdesk/reports/sla-breaches
     */
    public function index(): View
    {
        // Reasignación masiva reusa el endpoint bulk de HelpdeskTickets (el
        // mismo que consume la bulk-bar del listado de tickets) en vez de
        // duplicar assignTo()/TicketAssigned aquí. Sin el módulo, la ruta no
        // existe y la vista oculta los checkboxes.
        $bulkUrl = app('router')->has('manager.helpdesk.tickets.bulk')
            ? route('manager.helpdesk.tickets.bulk')
            : null;

        return view('helpdesk::helpdesk.reports.sla-breaches', [
            'bulkUrl' => $bulkUrl,
            'bulkAgents' => $bulkUrl ? $this->bulkAgents() : [],
        ]);
    }

    /**
     * Agentes disponibles para reasignar en masa. Deliberadamente la lista
     * completa de agentes del sistema (vía CatalogCacheService), no solo los
     * que ya aparecen en este reporte: la gracia de reasignar desde aquí es
     * poder repartir tickets "Sin asignar" hacia cualquier agente, no solo
     * hacia los que ya están sobrecargados.
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function bulkAgents(): array
    {
        $catalogClass = self::CATALOG_SERVICE;

        if (! class_exists($catalogClass)) {
            return [];
        }

        return $catalogClass::agents()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $this->formatAgentName($user->firstname, $user->lastname),
            ])
            ->values()
            ->all();
    }

    /**
     * GET /panel/helpdesk/reports/sla-breaches/data
     *
     * Returns breached tickets grouped by assignee plus the upcoming breaches
     * for the next 24 hours. Guards gracefully when HelpdeskTickets is disabled.
     */
    public function data(SlaBreachesReportDataRequest $request): JsonResponse
    {
        if (! $this->ticketsAvailable()) {
            return response()->json(['available' => false]);
        }

        $sla = app(self::SLA_SERVICE);

        $agentId = $request->filled('agent_id') ? (int) $request->input('agent_id') : null;

        $breached = $sla->getBreachedTickets($agentId);
        $upcoming = $sla->getUpcomingBreaches(24);

        $agentNames = $this->resolveAgentNames(
            $breached->pluck('assignee_id')
                ->merge($upcoming->pluck('assignee_id'))
                ->filter()
                ->unique()
                ->all()
        );

        return response()->json([
            'available' => true,
            // Total de tickets abiertos: el panel enseña "11 incumplidos de 21
            // abiertos" — sin el denominador el recuento no dice nada.
            'openTotal' => $sla->getOpenTicketCount(),
            'breachedByAgent' => $this->groupBreachedByAgent($breached, $agentNames),
            'upcoming' => $upcoming
                ->map(fn ($ticket): array => $this->mapTicket($ticket, $agentNames))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @param  array<int, string>  $agentNames
     * @return array<int, array{agentId: ?int, agentName: string, count: int, tickets: array<int, array<string, mixed>>}>
     */
    private function groupBreachedByAgent(Collection $breached, array $agentNames): array
    {
        return $breached
            ->groupBy(fn ($ticket) => $ticket->assignee_id ?? 0)
            ->map(function (Collection $tickets, $assigneeId) use ($agentNames): array {
                $assigneeId = (int) $assigneeId;

                return [
                    'agentId' => $assigneeId > 0 ? $assigneeId : null,
                    'agentName' => $assigneeId > 0
                        ? ($agentNames[$assigneeId] ?? 'Agente #'.$assigneeId)
                        : 'Sin asignar',
                    'count' => $tickets->count(),
                    'tickets' => $tickets
                        ->map(fn ($ticket): array => $this->mapTicket($ticket, $agentNames))
                        ->values()
                        ->all(),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $agentNames
     * @return array{id: int, number: ?string, subject: string, agentId: ?int, agentName: string, dueAt: ?string, overdueMinutes: int, url: ?string}
     */
    private function mapTicket(mixed $ticket, array $agentNames = []): array
    {
        $dueAt = $ticket->sla_resolution_due_at ?? null;
        $agentId = $ticket->assignee_id ? (int) $ticket->assignee_id : null;

        return [
            'id' => (int) $ticket->id,
            'number' => $ticket->ticket_number,
            'subject' => $ticket->subject ?? 'Sin asunto',
            // El agente viaja en cada fila y no solo en el grupo: la tabla del
            // panel es plana (una fila por ticket) y lo necesita por fila.
            'agentId' => $agentId,
            'agentName' => $agentId
                ? ($agentNames[$agentId] ?? 'Agente #'.$agentId)
                : 'Sin asignar',
            'dueAt' => $dueAt?->toIso8601String(),
            'overdueMinutes' => ($dueAt && $dueAt->isPast())
                ? (int) $dueAt->diffInMinutes(now())
                : 0,
            'url' => $this->ticketUrl((int) $ticket->id),
        ];
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, string>
     */
    private function resolveAgentNames(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return User::whereIn('id', $ids)
            ->get(['id', 'firstname', 'lastname'])
            ->mapWithKeys(fn (User $user): array => [
                $user->id => $this->formatAgentName($user->firstname, $user->lastname),
            ])
            ->all();
    }

    private function ticketUrl(int $ticketId): ?string
    {
        if (! app('router')->has('manager.helpdesk.tickets.show')) {
            return null;
        }

        return route('manager.helpdesk.tickets.show', $ticketId);
    }

    private function ticketsAvailable(): bool
    {
        $enabled = function_exists('helpdesk_tickets_enabled')
            ? helpdesk_tickets_enabled()
            : (Module::find('HelpdeskTickets')?->isEnabled() ?? false);

        return $enabled && class_exists(self::SLA_SERVICE);
    }
}
