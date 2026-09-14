<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route as RouteFacade;
use Modules\Helpdesk\Models\BusinessHour;
use Modules\HelpdeskSla\Models\Holiday;
use Modules\HelpdeskTickets\Http\Requests\Managers\UpdateSlaPauseStatusRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\SlaService;

/**
 * Datos del modal "Calendario y SLA" del panel de Gestión (modal 28).
 *
 * Controlador propio y nuevo: TicketOpsController::settingsSnapshot() sirve a
 * cuatro modales a la vez y su bloque 'sla_policy' se quedaba corto para esto
 * (una sola política y solo las columnas en minutos). No se toca para no
 * romper a los otros tres.
 *
 * Lo que este endpoint NO hace y por qué: no inventa ni el horario ni los
 * festivos. Ambos existen de verdad en esta instalación, pero en tres sitios
 * distintos y con alcances distintos, y el modal tiene que decir cuál manda:
 *
 *  1. helpdesk_ticket_sla_policies.business_hours (JSON por política) es el
 *     ÚNICO horario que usa el reloj de SLA de tickets
 *     (Ticket::calculateBusinessTime). Hoy está a NULL en todas: cuando
 *     business_hours_only está activo se cae al horario por defecto que lleva
 *     escrito ese método (L-V 09:00–17:00), no al calendario de la empresa.
 *  2. helpdesk_business_hours (rejilla semanal + zona horaria de la empresa)
 *     alimenta el SLA de CONVERSACIONES y el escalado de tickets, y este
 *     último solo si helpdesktickets.escalation.business_hours está activo.
 *  3. helpdesk_holidays (HelpdeskSla) sí lo consulta el reloj de tickets, pero
 *     únicamente dentro de la rama business_hours_only.
 *
 * Por eso el payload separa los tres bloques y marca el alcance de cada uno en
 * vez de fundirlos en un "horario laboral" que no existe como dato único.
 */
class TicketOpsSlaController extends Controller
{
    /**
     * Prioridades reales de un ticket (columna helpdesk_tickets.priority) y su
     * etiqueta. Es la lista cerrada que ya usan el selector del panel lateral y
     * los multiplicadores por defecto de TicketSlaPolicy.
     */
    private const TICKET_PRIORITIES = [
        'urgent' => 'Urgente',
        'high' => 'Alta',
        'normal' => 'Normal',
        'low' => 'Baja',
    ];

    /**
     * Etiquetas de la columna `priority` de las políticas. Ojo: ese vocabulario
     * NO coincide con el de los tickets (las políticas sembradas usan
     * medium/critical, que no son valores posibles en helpdesk_tickets), así
     * que se etiqueta lo que se pueda y el resto se muestra en crudo en vez de
     * mapearlo a la fuerza a una prioridad de ticket que no le corresponde.
     */
    private const POLICY_PRIORITY_LABELS = [
        'critical' => 'Crítica',
        'urgent' => 'Urgente',
        'high' => 'Alta',
        'medium' => 'Media',
        'normal' => 'Normal',
        'low' => 'Baja',
    ];

    /**
     * Horario por defecto escrito dentro de Ticket::calculateBusinessTime():
     * es el que corre de verdad cuando la política pide horas hábiles pero no
     * trae su JSON. Se replica aquí para poder ENSEÑARLO tal cual, no para
     * proponerlo como valor por defecto de nada.
     */
    private const POLICY_HOURS_FALLBACK = [
        'monday' => ['start' => '09:00', 'end' => '17:00'],
        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
        'thursday' => ['start' => '09:00', 'end' => '17:00'],
        'friday' => ['start' => '09:00', 'end' => '17:00'],
    ];

    /** GET · todo lo que pinta el modal, en una sola llamada. */
    public function calendar(Request $request, SlaService $sla): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        return response()->json([
            'ticket' => $this->ticketClock($request, $sla),
            'policies' => $this->policies(),
            'business_hours' => $this->businessHours(),
            'holidays' => $this->holidays(),
            'pause' => $this->pauseSettings($request),
            'links' => $this->links(),
        ]);
    }

    /**
     * POST · marca/desmarca un estado como "pausa el reloj".
     *
     * Solo cambia el catálogo: NO pausa ni reanuda los tickets que ya están en
     * ese estado. Pausar en bloque desplazaría los vencimientos de tickets que
     * llevan días parados ahí y falsearía el histórico; el flag actúa en la
     * transición de estado (TicketUpdateService), que es donde el producto ya
     * decide pausar y reanudar.
     */
    public function updatePauseStatus(UpdateSlaPauseStatusRequest $request): JsonResponse
    {
        $status = TicketStatus::query()->findOrFail($request->integer('status_id'));
        $stops = $request->boolean('stops_sla_timer');

        $status->update(['stops_sla_timer' => $stops]);

        return response()->json([
            'message' => $stops
                ? "«{$status->name}» pasa a pausar el reloj de SLA."
                : "«{$status->name}» ya no pausa el reloj de SLA.",
            'status' => $this->statusRow($status->refresh()),
        ]);
    }

    /**
     * Reloj de SLA del ticket abierto. `?ticket=` es opcional: el modal también
     * se puede abrir sin ficha seleccionada, y entonces solo se ven los
     * ajustes globales.
     */
    private function ticketClock(Request $request, SlaService $sla): ?array
    {
        $ticketId = $request->integer('ticket');

        if ($ticketId <= 0) {
            return null;
        }

        $ticket = Ticket::query()->with(['slaPolicy', 'status'])->find($ticketId);

        if (! $ticket) {
            return null;
        }

        $this->authorize('view', $ticket);

        $pausedAt = $ticket->sla_paused_at;

        return [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'priority' => $ticket->priority,
            'priority_label' => self::TICKET_PRIORITIES[$ticket->priority] ?? $ticket->priority,
            'policy' => $ticket->slaPolicy
                ? ['id' => $ticket->slaPolicy->id, 'name' => $ticket->slaPolicy->name]
                : null,
            'status' => $ticket->status ? [
                'id' => $ticket->status->id,
                'name' => $ticket->status->name,
                'stops_sla' => (bool) $ticket->status->stops_sla_timer,
            ] : null,
            'paused' => $pausedAt !== null,
            'paused_at' => $pausedAt?->toIso8601String(),
            'paused_since_human' => $pausedAt?->diffForHumans(),
            // Minutos de la pausa EN CURSO, separados del acumulado histórico:
            // son los que resumeSla() sumará a cada vencimiento al reanudar.
            'current_pause_minutes' => $pausedAt ? (int) $pausedAt->diffInMinutes(now()) : 0,
            'accumulated_pause_minutes' => (int) ($ticket->sla_paused_duration_minutes ?? 0),
            'due' => [
                'first_response' => $this->dueRow($ticket->sla_first_response_due_at, (bool) $ticket->sla_first_response_breached),
                'next_response' => $this->dueRow($ticket->sla_next_response_due_at, (bool) $ticket->sla_next_response_breached),
                'resolution' => $this->dueRow($ticket->sla_resolution_due_at, (bool) $ticket->sla_resolution_breached),
            ],
            // Vencimiento de resolución compensando la pausa en curso: es el
            // que decide de verdad si el ticket ha incumplido.
            'effective_resolution_due_at' => $sla->getEffectiveDueDate($ticket)?->toIso8601String(),
            'first_response_at' => $ticket->first_response_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{at: string|null, human: string|null, breached: bool}
     */
    private function dueRow(?Carbon $due, bool $breached): array
    {
        return [
            'at' => $due?->toIso8601String(),
            'human' => $due?->diffForHumans(),
            'breached' => $breached,
        ];
    }

    /**
     * Políticas activas con sus objetivos. Se devuelven las dos familias de
     * columnas por separado a propósito:
     *
     *  - *_time (minutos): las que lee Ticket::calculateSlaDueDates(). Si están
     *    a NULL, la política no fija NINGÚN vencimiento por mucho que la
     *    pantalla de ajustes enseñe horas.
     *  - *_time_hours: columnas heredadas que añadió la migración de
     *    alineación de esquema y que rellenó el seeder. Son las que hoy tienen
     *    dato real, y ningún cálculo las mira.
     *
     * `clock_enforced` es esa diferencia resuelta en un booleano para que el
     * modal pueda avisar en vez de prometer un plazo que no se aplica.
     *
     * @return array<int, array<string, mixed>>
     */
    private function policies(): array
    {
        return TicketSlaPolicy::query()
            ->where('active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->map(function (TicketSlaPolicy $policy): array {
                $multipliers = $policy->priority_multipliers ?: [
                    'urgent' => 0.25,
                    'high' => 0.5,
                    'normal' => 1.0,
                    'low' => 2.0,
                ];

                $firstResponse = $policy->first_response_time !== null ? (int) $policy->first_response_time : null;
                $nextResponse = $policy->next_response_time !== null ? (int) $policy->next_response_time : null;
                $resolution = $policy->resolution_time !== null ? (int) $policy->resolution_time : null;

                $clockEnforced = $firstResponse !== null || $nextResponse !== null || $resolution !== null;

                // Horario que corre de verdad para esta política.
                $hours = $policy->business_hours;
                $usesFallbackHours = (bool) $policy->business_hours_only && empty($hours);

                return [
                    'id' => $policy->id,
                    'name' => $policy->name,
                    'is_default' => (bool) $policy->is_default,
                    'channel' => $policy->channel,
                    'priority' => $policy->priority,
                    'priority_label' => $policy->priority
                        ? (self::POLICY_PRIORITY_LABELS[$policy->priority] ?? $policy->priority)
                        : null,
                    'first_response_minutes' => $firstResponse,
                    'next_response_minutes' => $nextResponse,
                    'resolution_minutes' => $resolution,
                    'declared_hours' => [
                        'first_response' => $policy->first_response_time_hours !== null ? (int) $policy->first_response_time_hours : null,
                        'next_response' => $policy->next_response_time_hours !== null ? (int) $policy->next_response_time_hours : null,
                        'resolution' => $policy->resolution_time_hours !== null ? (int) $policy->resolution_time_hours : null,
                    ],
                    'clock_enforced' => $clockEnforced,
                    'business_hours_only' => (bool) $policy->business_hours_only,
                    'business_hours' => $hours,
                    'business_hours_fallback' => $usesFallbackHours ? self::POLICY_HOURS_FALLBACK : null,
                    'timezone' => $policy->timezone ?: 'UTC',
                    'enable_escalation' => (bool) $policy->enable_escalation,
                    'escalation_threshold_percent' => $policy->escalation_threshold_percent !== null
                        ? (int) $policy->escalation_threshold_percent
                        : null,
                    'multipliers' => $this->multiplierRows($multipliers),
                    'targets_by_priority' => $clockEnforced
                        ? $this->targetsByPriority($firstResponse, $resolution, $multipliers)
                        : [],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, float|int|string>  $multipliers
     * @return array<int, array{priority: string, label: string, multiplier: float}>
     */
    private function multiplierRows(array $multipliers): array
    {
        $rows = [];

        foreach (self::TICKET_PRIORITIES as $priority => $label) {
            $rows[] = [
                'priority' => $priority,
                'label' => $label,
                'multiplier' => (float) ($multipliers[$priority] ?? 1.0),
            ];
        }

        return $rows;
    }

    /**
     * Objetivo efectivo por prioridad = base de la política × multiplicador,
     * exactamente el mismo cálculo que hace calculateSlaDueDates(). Solo se
     * emite cuando la base existe; con la base a NULL no hay objetivo que
     * mostrar y devolver el multiplicador a secas sería un número sin unidad.
     *
     * @param  array<string, float|int|string>  $multipliers
     * @return array<int, array<string, mixed>>
     */
    private function targetsByPriority(?int $firstResponse, ?int $resolution, array $multipliers): array
    {
        $rows = [];

        foreach (self::TICKET_PRIORITIES as $priority => $label) {
            $multiplier = (float) ($multipliers[$priority] ?? 1.0);

            $rows[] = [
                'priority' => $priority,
                'label' => $label,
                'multiplier' => $multiplier,
                'first_response_minutes' => $firstResponse !== null ? (int) ($firstResponse * $multiplier) : null,
                'resolution_minutes' => $resolution !== null ? (int) ($resolution * $multiplier) : null,
            ];
        }

        return $rows;
    }

    /**
     * Rejilla semanal de la empresa (helpdesk_business_hours). Se publica con
     * su alcance real: el reloj de SLA de tickets no la mira, y el escalado
     * solo si el toggle de horas hábiles está encendido.
     *
     * @return array<string, mixed>
     */
    private function businessHours(): array
    {
        $rows = BusinessHour::query()->orderBy('day_of_week')->get();

        return [
            'configured' => $rows->isNotEmpty(),
            'timezone' => $rows->firstWhere(fn (BusinessHour $h) => (bool) $h->timezone)?->timezone,
            // El escalado de tickets es el único consumidor de esta rejilla
            // dentro de HelpdeskTickets, y va detrás de este toggle.
            'used_by_escalation' => (bool) config('helpdesktickets.escalation.business_hours', false),
            'days' => $rows->map(fn (BusinessHour $hour) => [
                'day_of_week' => (int) $hour->day_of_week,
                'name' => $hour->day_name,
                'is_open' => (bool) $hour->is_open,
                'opens_at' => $hour->opens_at ? substr((string) $hour->opens_at, 0, 5) : null,
                'closes_at' => $hour->closes_at ? substr((string) $hour->closes_at, 0, 5) : null,
            ])->values()->all(),
        ];
    }

    /**
     * Festivos del calendario de negocio, ordenados por su PRÓXIMA aparición:
     * los recurrentes se repiten cada año (BusinessHoursCalculator los indexa
     * por 'm-d'), así que uno de enero marcado como recurrente sigue vigente en
     * septiembre y tiene que salir con la fecha del año que viene, no con la
     * del año en que se dio de alta.
     *
     * @return array<string, mixed>
     */
    private function holidays(): array
    {
        $today = Carbon::today();
        $upcoming = [];
        $total = 0;

        // Dependencia blanda, igual que en EscalationService y en
        // Ticket::calculateBusinessTime(): sin el módulo HelpdeskSla no hay
        // tabla de festivos y el bloque se declara "no configurado" en vez de
        // reventar el modal entero.
        if (! class_exists(Holiday::class)) {
            return ['total' => 0, 'applies_to_ticket_sla' => false, 'upcoming' => [], 'available' => false];
        }

        foreach (Holiday::query()->get() as $holiday) {
            if (! $holiday->date) {
                continue;
            }

            $total++;

            $next = $holiday->is_recurring
                ? $this->nextRecurrence($holiday->date, $today)
                : $holiday->date->copy();

            if ($next->lessThan($today)) {
                continue;
            }

            $upcoming[] = [
                'name' => $holiday->name,
                'date' => $next->toDateString(),
                'is_recurring' => (bool) $holiday->is_recurring,
                'days_away' => (int) $today->diffInDays($next),
            ];
        }

        usort($upcoming, fn (array $a, array $b) => strcmp($a['date'], $b['date']));

        return [
            'available' => true,
            'total' => $total,
            // El reloj de tickets consulta los festivos SOLO dentro de la rama
            // de horas hábiles: sin business_hours_only no los tiene en cuenta.
            'applies_to_ticket_sla' => TicketSlaPolicy::query()
                ->where('active', true)
                ->where('business_hours_only', true)
                ->exists(),
            'upcoming' => array_slice($upcoming, 0, 6),
        ];
    }

    private function nextRecurrence(Carbon $date, Carbon $today): Carbon
    {
        $candidate = $date->copy()->setYear($today->year);

        return $candidate->lessThan($today)
            ? $candidate->addYear()
            : $candidate;
    }

    /**
     * Catálogo de estados con el flag que gobierna la pausa automática.
     *
     * La reanudación NO es configurable: la disparan el cambio de estado
     * (TicketUpdateService) y la respuesta del cliente por el portal
     * (CustomerPortalController). Se publica como booleano informativo para
     * que el modal lo diga en vez de fingir un interruptor que no existe.
     *
     * @return array<string, mixed>
     */
    private function pauseSettings(Request $request): array
    {
        return [
            'can_manage' => (bool) $request->user()?->can('helpdesk.tickets.settings'),
            'resume_is_automatic' => true,
            'statuses' => TicketStatus::query()
                ->orderBy('order')
                ->orderBy('id')
                ->get()
                ->map(fn (TicketStatus $status) => $this->statusRow($status))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function statusRow(TicketStatus $status): array
    {
        return [
            'id' => $status->id,
            'name' => $status->name,
            'slug' => $status->slug,
            'stops_sla' => (bool) $status->stops_sla_timer,
            'is_closed' => (bool) $status->is_closed,
        ];
    }

    /**
     * Enlaces a las pantallas donde se configura cada bloque. Los de festivos
     * dependen de un módulo opcional (HelpdeskSla, con su propio toggle de
     * integración): si no está, el modal simplemente no pinta el botón.
     *
     * @return array<string, string|null>
     */
    private function links(): array
    {
        $holidaysEnabled = function_exists('helpdesk_sla_enabled')
            ? helpdesk_sla_enabled()
            : false;

        return [
            'sla_policies' => RouteFacade::has('manager.helpdesk.settings.ticket-sla-policies.index')
                ? route('manager.helpdesk.settings.ticket-sla-policies.index')
                : null,
            'statuses' => RouteFacade::has('manager.helpdesk.settings.ticket-statuses.index')
                ? route('manager.helpdesk.settings.ticket-statuses.index')
                : null,
            'business_hours' => RouteFacade::has('settings.helpdesk.business.hours')
                ? route('settings.helpdesk.business.hours')
                : null,
            'holidays' => $holidaysEnabled && RouteFacade::has('helpdesksla.holidays.index')
                ? route('helpdesksla.holidays.index')
                : null,
        ];
    }
}
