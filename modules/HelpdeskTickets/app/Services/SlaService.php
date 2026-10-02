<?php

namespace Modules\HelpdeskTickets\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSla\Services\BusinessHoursCalculator;
use Modules\HelpdeskTickets\Events\SlaBreachBroadcast;
use Modules\HelpdeskTickets\Events\SlaBreached;
use Modules\HelpdeskTickets\Events\TicketSlaBreached;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketSlaBreach;

/**
 * Dueño único de la aritmética de SLA de tickets.
 *
 * Hasta ahora esta clase no la llamaba nadie: se inyectaba en TicketService y
 * en CheckSlaBreaches y ninguno de los dos tocaba la variable. La lógica real
 * vivía duplicada en Ticket (pauseSla/resumeSla/slaEffectiveDueDate) y en
 * CheckSlaBreaches::handle(), y las copias ya habían divergido — la del modelo
 * extendía sla_next_response_due_at al reanudar y la de aquí se lo dejaba.
 * Peor: SlaServiceTest probaba en detalle la copia muerta, así que los tests
 * del SLA estaban en verde sin cubrir el camino que corría en producción.
 *
 * Ahora la implementación buena (la del modelo) vive aquí, Ticket delega y el
 * job delega. Los métodos de Ticket se mantienen como fachada porque los llaman
 * las vistas y TicketUpdateService.
 */
class SlaService
{
    /**
     * Marca como incumplidos los tickets cuyo plazo de resolución ha vencido.
     *
     * @return Collection<int, Ticket>
     */
    public function checkBreaches(): Collection
    {
        try {
            $breachedTickets = collect();

            Ticket::where('sla_resolution_breached', false)
                ->whereNotNull('sla_resolution_due_at')
                ->where('sla_resolution_due_at', '<', now())
                // Un ticket con el reloj pausado (estado con stops_sla_timer,
                // típicamente "en espera del cliente") NO ha incumplido aunque
                // su fecha nominal haya pasado: la pausa se compensa en
                // effectiveDueDate(). Sin este filtro la lista pintaba el
                // ticket en verde mientras el manager recibía el correo de
                // incumplimiento. Al reanudar, resumeSla() desplaza las fechas
                // y el ticket vuelve a entrar solo en esta consulta.
                ->whereNull('sla_paused_at')
                ->whereNull('closed_at')
                ->lazyById(500)
                ->each(function (Ticket $ticket) use ($breachedTickets) {
                    $this->registerResolutionBreach($ticket);

                    $breachedTickets->push($ticket);
                });

            // Primera y siguiente respuesta se barren siempre, sin depender
            // del toggle auto_overdue_ticket de ticket:autooverdue (que por
            // defecto está apagado y marcaba los flags con un UPDATE masivo
            // sin eventos ni registro de incumplimiento). El valor devuelto
            // sigue siendo solo el de resolución.
            $this->sweepResponseBreaches();

            return $breachedTickets;
        } catch (\Exception $e) {
            Log::error('Error checking SLA breaches', ['error' => $e->getMessage()]);

            return collect();
        }
    }

    /**
     * Marca un ticket como incumplido en resolución y avisa (una sola vez).
     *
     * Camino único para el barrido de CheckSlaBreaches y para
     * ticket:autooverdue: quien ya tiene el flag no se vuelve a notificar.
     *
     * @return bool false si ya estaba marcado (no se notifica de nuevo)
     */
    public function registerResolutionBreach(Ticket $ticket): bool
    {
        if (! $this->claimBreachFlag($ticket, 'sla_resolution_breached')) {
            return false;
        }

        // El correo de incumplimiento lo envía el listener
        // SendSlaBreachNotification (SlaBreachMail) suscrito a este
        // evento — no duplicar aquí. El broadcast es el aviso en
        // tiempo real al panel.
        event(new SlaBreached($ticket));
        SlaBreachBroadcast::dispatch($ticket);

        // Aviso en el panel/push al asignado y al canal de equipo
        // (Slack/Teams): lo hace SendSlaBreachBroadcastNotification.
        // Aislado: un fallo al avisar de un ticket no puede cortar
        // el barrido del resto.
        try {
            TicketSlaBreached::dispatch($ticket, $this->breachRecord($ticket, 'resolution', $ticket->sla_resolution_due_at));
        } catch (\Throwable $e) {
            Log::warning('SLA breach alert failed', ['ticket_id' => $ticket->id, 'error' => $e->getMessage()]);
        }

        Log::warning('SLA breach detected', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'due_at' => $ticket->sla_resolution_due_at,
        ]);

        return true;
    }

    /**
     * Marca la primera respuesta como incumplida y avisa (una sola vez).
     *
     * @return bool false si ya estaba marcada (no se notifica de nuevo)
     */
    public function registerFirstResponseBreach(Ticket $ticket): bool
    {
        return $this->registerResponseBreach($ticket, 'first_response', 'sla_first_response_breached', $ticket->sla_first_response_due_at);
    }

    /**
     * Marca la siguiente respuesta como incumplida y avisa (una sola vez).
     *
     * @return bool false si ya estaba marcada (no se notifica de nuevo)
     */
    public function registerNextResponseBreach(Ticket $ticket): bool
    {
        return $this->registerResponseBreach($ticket, 'next_response', 'sla_next_response_breached', $ticket->sla_next_response_due_at);
    }

    private function sweepResponseBreaches(): void
    {
        // Sin sla_first_response_due_at no hay plazo que incumplir, y con
        // first_response_at ya se respondió. Los pausados no incumplen (ver
        // checkBreaches()).
        Ticket::query()
            ->where('sla_first_response_breached', false)
            ->whereNull('first_response_at')
            ->where('sla_first_response_due_at', '<', now())
            ->whereNull('sla_paused_at')
            ->whereNull('closed_at')
            ->lazyById(500)
            ->each(fn (Ticket $ticket) => $this->registerFirstResponseBreach($ticket));

        // Sin vencimiento (agente ya respondió / esperando al cliente) no hay
        // respuesta pendiente: no es incumplimiento.
        Ticket::query()
            ->where('sla_next_response_breached', false)
            ->where('sla_next_response_due_at', '<', now())
            ->whereNull('sla_paused_at')
            ->whereNull('closed_at')
            ->lazyById(500)
            ->each(fn (Ticket $ticket) => $this->registerNextResponseBreach($ticket));
    }

    /**
     * El correo SlaBreachMail (evento SlaBreached) habla del plazo de
     * resolución, así que para las respuestas solo se deja el registro de
     * auditoría y el aviso en el panel/canal de equipo (TicketSlaBreached).
     */
    private function registerResponseBreach(Ticket $ticket, string $type, string $flag, ?Carbon $dueAt): bool
    {
        if (! $this->claimBreachFlag($ticket, $flag)) {
            return false;
        }

        try {
            TicketSlaBreached::dispatch($ticket, $this->breachRecord($ticket, $type, $dueAt));
        } catch (\Throwable $e) {
            Log::warning('SLA breach alert failed', ['ticket_id' => $ticket->id, 'type' => $type, 'error' => $e->getMessage()]);
        }

        Log::warning('SLA breach detected', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'type' => $type,
            'due_at' => $dueAt,
        ]);

        return true;
    }

    /**
     * Registro atómico del flag: el UPDATE condicionado solo afecta a una fila
     * para quien llegue primero, así que dos barridos concurrentes (job de
     * cada 15 min y ticket:autooverdue) no notifican dos veces el mismo
     * incumplimiento. Sin observers a propósito: es un sweep por lotes y no
     * queremos historial/embeddings por cada fila solo por marcar el flag.
     */
    private function claimBreachFlag(Ticket $ticket, string $flag): bool
    {
        $affected = Ticket::query()
            ->whereKey($ticket->getKey())
            ->where($flag, false)
            ->update([$flag => true]);

        if ($affected !== 1) {
            return false;
        }

        $ticket->setAttribute($flag, true);
        $ticket->syncOriginalAttribute($flag);

        return true;
    }

    /**
     * Calculate response time in minutes
     */
    public function calculateResponseTime(Ticket $ticket): ?int
    {
        if (! $ticket->first_response_at) {
            return null;
        }

        return $ticket->created_at->diffInMinutes($ticket->first_response_at);
    }

    /**
     * Calculate resolution time in minutes
     */
    public function calculateResolutionTime(Ticket $ticket): ?int
    {
        if (! $ticket->closed_at) {
            return null;
        }

        return $ticket->created_at->diffInMinutes($ticket->closed_at);
    }

    /**
     * Pausa el reloj de SLA (p. ej. al quedar en espera del cliente).
     */
    public function pauseSla(Ticket $ticket): void
    {
        if ($ticket->sla_paused_at !== null) {
            return;
        }

        $ticket->update(['sla_paused_at' => now()]);
    }

    /**
     * Reanuda el reloj y desplaza los vencimientos por el tiempo pausado.
     *
     * sla_next_response_due_at se desplaza igual que los otros dos: la copia
     * que vivía aquí se lo dejaba fuera, así que el plazo de "siguiente
     * respuesta" no recuperaba el tiempo de espera del cliente.
     */
    public function resumeSla(Ticket $ticket): void
    {
        if ($ticket->sla_paused_at === null) {
            return;
        }

        // Política de horas hábiles: el tiempo en pausa se mide y se suma en
        // minutos hábiles (como ConversationSlaService::resumeSla). Pausar el
        // viernes a las 17:00 y reanudar el lunes a las 09:00 no consume plazo.
        $businessOnly = (bool) $ticket->slaPolicy?->business_hours_only
            && class_exists(BusinessHoursCalculator::class);
        $calculator = $businessOnly ? app(BusinessHoursCalculator::class) : null;

        $pausedMinutes = $calculator
            ? $calculator->businessMinutesBetween($ticket->sla_paused_at, now())
            : (int) $ticket->sla_paused_at->diffInMinutes(now());

        $updates = [
            'sla_paused_duration_minutes' => ($ticket->sla_paused_duration_minutes ?? 0) + $pausedMinutes,
            'sla_paused_at' => null,
        ];

        foreach (['sla_first_response_due_at', 'sla_next_response_due_at', 'sla_resolution_due_at'] as $field) {
            if ($ticket->{$field}) {
                $updates[$field] = $calculator
                    ? $calculator->addBusinessMinutes($ticket->{$field}, $pausedMinutes)
                    : $ticket->{$field}->copy()->addMinutes($pausedMinutes);
            }
        }

        $ticket->update($updates);
    }

    /**
     * Cuántos tickets hay abiertos en total, para dar contexto al recuento de
     * incumplidos ("11 de 21"): un 11 a secas no dice si el equipo va mal o
     * si es que hay muchísimo volumen.
     *
     * Vive aquí y no en el controlador del informe porque Helpdesk no puede
     * depender de HelpdeskTickets — el informe llega a este servicio por FQCN
     * en string, tras el guard de Module::find().
     */
    public function getOpenTicketCount(): int
    {
        return Ticket::query()->whereNull('closed_at')->count();
    }

    /**
     * Tickets con SLA de resolución ya incumplido, opcionalmente filtrados
     * por agente asignado. Mismas reglas que checkBreaches() (abierto, sin
     * pausar) pero calculado en vivo sobre sla_resolution_due_at en vez de
     * depender del flag persistido sla_resolution_breached — así el reporte
     * no queda desactualizado entre corridas del job CheckSlaBreaches (cada
     * 15 minutos).
     *
     * @return Collection<int, Ticket>
     */
    public function getBreachedTickets(?int $agentId = null): Collection
    {
        return Ticket::query()
            ->whereNotNull('sla_resolution_due_at')
            ->where('sla_resolution_due_at', '<', now())
            ->whereNull('sla_paused_at')
            ->whereNull('closed_at')
            ->when($agentId !== null, fn ($q) => $q->where('assignee_id', $agentId))
            ->orderBy('sla_resolution_due_at')
            ->get(['id', 'ticket_number', 'subject', 'sla_resolution_due_at', 'assignee_id']);
    }

    /**
     * Tickets abiertos cuyo vencimiento de resolución cae dentro de las
     * próximas $hours horas y que aún no han incumplido. Excluye los
     * pausados: con el reloj parado no están "por vencer" en un sentido
     * real (su vencimiento efectivo se corre — ver getEffectiveDueDate()).
     *
     * @return Collection<int, Ticket>
     */
    public function getUpcomingBreaches(int $hours): Collection
    {
        return Ticket::query()
            ->whereNotNull('sla_resolution_due_at')
            ->whereBetween('sla_resolution_due_at', [now(), now()->addHours($hours)])
            ->whereNull('sla_paused_at')
            ->whereNull('closed_at')
            ->orderBy('sla_resolution_due_at')
            ->get(['id', 'ticket_number', 'subject', 'sla_resolution_due_at', 'assignee_id']);
    }

    /**
     * Vencimiento de resolución efectivo, compensando la pausa en curso.
     */
    public function getEffectiveDueDate(Ticket $ticket): ?Carbon
    {
        if ($ticket->sla_resolution_due_at === null) {
            return null;
        }

        if ($ticket->sla_paused_at !== null) {
            $currentPauseMinutes = $ticket->sla_paused_at->diffInMinutes(now());

            return $ticket->sla_resolution_due_at->copy()->addMinutes($currentPauseMinutes);
        }

        return $ticket->sla_resolution_due_at;
    }

    /**
     * Registro de auditoría del incumplimiento: reutiliza el abierto si ya lo
     * creó el escalado (mismo criterio que EscalationService) para no duplicar
     * filas.
     */
    private function breachRecord(Ticket $ticket, string $type, ?Carbon $dueAt): TicketSlaBreach
    {
        $existing = TicketSlaBreach::query()
            ->where('ticket_id', $ticket->id)
            ->where('breach_type', $type)
            ->unresolved()
            ->first();

        if ($existing) {
            return $existing;
        }

        return TicketSlaBreach::create([
            'ticket_id' => $ticket->id,
            'breach_type' => $type,
            'due_at' => $dueAt,
            'breached_at' => now(),
            'breach_duration_minutes' => $dueAt !== null ? (int) round(abs($dueAt->diffInMinutes(now()))) : null,
            'metadata' => ['recorded_by' => 'sla_sweep'],
        ]);
    }
}
