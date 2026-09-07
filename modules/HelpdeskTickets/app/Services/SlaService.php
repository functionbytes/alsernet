<?php

namespace Modules\HelpdeskTickets\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\SlaBreachBroadcast;
use Modules\HelpdeskTickets\Events\SlaBreached;
use Modules\HelpdeskTickets\Models\Ticket;

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
                ->cursor()
                ->each(function (Ticket $ticket) use ($breachedTickets) {
                    // updateQuietly: es un sweep por lotes, no queremos disparar
                    // los observers del ticket (historial, embeddings…) por cada
                    // fila solo por marcar el flag. El aviso va por el evento SLA.
                    $ticket->updateQuietly(['sla_resolution_breached' => true]);

                    // El correo de incumplimiento lo envía el listener
                    // SendSlaBreachNotification (SlaBreachMail) suscrito a este
                    // evento — no duplicar aquí. El broadcast es el aviso en
                    // tiempo real al panel.
                    event(new SlaBreached($ticket));
                    SlaBreachBroadcast::dispatch($ticket);

                    Log::warning('SLA breach detected', [
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'due_at' => $ticket->sla_resolution_due_at,
                    ]);

                    $breachedTickets->push($ticket);
                });

            return $breachedTickets;
        } catch (\Exception $e) {
            Log::error('Error checking SLA breaches', ['error' => $e->getMessage()]);

            return collect();
        }
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

        $pausedMinutes = $ticket->sla_paused_at->diffInMinutes(now());

        $updates = [
            'sla_paused_duration_minutes' => ($ticket->sla_paused_duration_minutes ?? 0) + $pausedMinutes,
            'sla_paused_at' => null,
        ];

        foreach (['sla_first_response_due_at', 'sla_next_response_due_at', 'sla_resolution_due_at'] as $field) {
            if ($ticket->{$field}) {
                $updates[$field] = $ticket->{$field}->copy()->addMinutes($pausedMinutes);
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
}
