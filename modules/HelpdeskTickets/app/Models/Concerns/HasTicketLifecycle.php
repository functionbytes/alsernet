<?php

namespace Modules\HelpdeskTickets\Models\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskTickets\Models\TicketStatus;

trait HasTicketLifecycle
{
    /**
     * Check if ticket is resolved
     */
    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * Get unread messages count for a user
     */
    public function getUnreadCountForUser($userId): int
    {
        return $this->messages()
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $userId))
            ->count();
    }

    /**
     * Assign ticket to agent
     */
    public function assignTo($userId): self
    {
        $this->update([
            'assignee_id' => $userId,
            'assigned_at' => now(),
        ]);

        // Create system event
        $this->items()->create([
            'type' => 'assigned',
            'user_id' => $userId,
            // fullName() y no ->name: el User de esta app no tiene atributo
            // 'name' (guarda firstname/lastname), así que la línea del hilo
            // salía literalmente "Ticket assigned to " sin nadie detrás.
            // Se resuelve por $userId y no por la relación $this->assignee,
            // que sigue cacheada con el agente ANTERIOR justo después del
            // update() de arriba.
            'body' => 'Ticket asignado a '.(User::find($userId)?->fullName() ?: 'un agente'),
            'metadata' => ['assignee_id' => $userId],
        ]);

        return $this;
    }

    /**
     * Close ticket
     *
     * $reason es una key de config('helpdesktickets.close_reasons') (o texto
     * libre si viene del "Otro motivo" del modal) — puede venir vacío cuando
     * se cierra desde bulk actions o el flujo de un agente IA, que no piden
     * motivo.
     *
     * Bug real encontrado al probar el modal "Cerrar ticket" (ago-2026): la
     * caché resolvía el status por is_open=false + order ASC, que da "En
     * Espera" (order=4, is_open=false) en vez de "Cerrado" (order=8, slug
     * closed) — "Cerrar ticket" dejaba el ticket en En Espera, no Cerrado.
     * Mismo criterio por slug que ya usa resolve() (ver su docblock).
     */
    /**
     * @param  array{root_cause?: ?string, summary?: ?string, skip_survey?: bool}  $analysis
     *                                                                                        Clasificación del cierre para los informes: causa raíz y resumen del
     *                                                                                        agente. Va aparte de $reason porque el motivo dice cómo acabó el
     *                                                                                        ticket y la causa raíz por qué existió.
     */
    public function close(?string $reason = null, array $analysis = []): self
    {
        $closedStatus = Cache::remember('helpdesk:closed-status', 3600, fn () => TicketStatus::where('slug', 'closed')->first());

        $this->update([
            'status_id' => $closedStatus->id ?? $this->status_id,
            'closed_at' => now(),
            'close_reason' => $reason ?: $this->close_reason,
            'close_root_cause' => $analysis['root_cause'] ?? $this->close_root_cause,
            'close_summary' => $analysis['summary'] ?? $this->close_summary,
            // ?? false al final: close_skip_survey es NOT NULL, y en una
            // instancia recién creada con Ticket::create() el atributo todavía
            // no está hidratado desde la BD — cerrarlo ahí mismo (lo hace la
            // unificación de duplicados) reventaba con "cannot be null".
            'close_skip_survey' => $analysis['skip_survey'] ?? $this->close_skip_survey ?? false,
        ]);

        // Create system event
        $this->items()->create([
            'type' => 'closed',
            'body' => 'Ticket cerrado',
        ]);

        return $this;
    }

    /**
     * Resolve ticket.
     *
     * Bug real encontrado en QA de la pantalla "Gestión de tickets" (ago-2026):
     * este método solo marcaba resolved_at sin tocar status_id — a diferencia
     * de close()/reopen(), que sí resuelven y asignan el TicketStatus real.
     * Resultado: el botón "Resolver" (bulk y ficha) respondía success:true
     * pero el ticket seguía apareciendo "Abierto" en cualquier listado, porque
     * el estado visible se deriva de status_id, no de resolved_at. Mismo
     * patrón de caché que close()/reopen().
     */
    public function resolve(): self
    {
        $resolvedStatus = Cache::remember('helpdesk:resolved-status', 3600, fn () => TicketStatus::where('slug', 'resolved')->first());

        $this->update([
            'status_id' => $resolvedStatus->id ?? $this->status_id,
            'resolved_at' => now(),
        ]);

        // Create system event
        $this->items()->create([
            'type' => 'status_change',
            'body' => 'Ticket resuelto',
            'metadata' => ['resolved_at' => now()->toIso8601String()],
        ]);

        return $this;
    }

    /**
     * Primer estado abierto del catálogo por orden — red de seguridad para
     * cuando no hay un slug/is_default concreto que resolver. Antes vivía
     * inline solo en reopen(); TicketObserver::creating() lo reutiliza ahora
     * como último fallback si el catálogo se queda sin ningún is_default
     * (bug real: los tickets creados sin status_id en local salían de ahí).
     */
    public static function firstOpenTicketStatus(): ?TicketStatus
    {
        return TicketStatus::where('is_open', true)->orderBy('order')->first();
    }

    /**
     * Reopen ticket
     */
    public function reopen(): self
    {
        // "Reabierto" por slug; antes era el primer estado abierto por orden,
        // que es "Nuevo", y un ticket reabierto se confundía con uno recién
        // llegado en listados e informes.
        $openStatus = Cache::remember('helpdesk:reopened-status', 3600, fn () => TicketStatus::where('slug', 'reopened')->first()
            ?? static::firstOpenTicketStatus());

        $this->update([
            'status_id' => $openStatus->id ?? $this->status_id,
            'closed_at' => null,
            'resolved_at' => null,
            // El plazo de resolución vuelve a contar desde ahora (abajo), así
            // que el incumplimiento del ciclo anterior no aplica al nuevo.
            'sla_resolution_breached' => false,
        ]);

        // Recalculate SLA if policy exists
        if ($this->sla_policy_id) {
            $this->calculateSlaDueDates();
        }

        // Create system event
        $this->items()->create([
            'type' => 'reopened',
            'body' => 'Ticket reabierto',
        ]);

        return $this;
    }

    /**
     * Get time to first response (in minutes)
     */
    public function getTimeToFirstResponse(): ?int
    {
        if (! $this->first_response_at) {
            return null;
        }

        return $this->created_at->diffInMinutes($this->first_response_at);
    }

    /**
     * Get time to resolution (in minutes)
     */
    public function getTimeToResolution(): ?int
    {
        if (! $this->resolved_at) {
            return null;
        }

        return $this->created_at->diffInMinutes($this->resolved_at) - $this->sla_paused_duration_minutes;
    }

    /**
     * Get ticket duration (if closed)
     */
    public function getDuration(): int
    {
        $end = $this->closed_at ?? now();

        return $this->created_at->diffInMinutes($end);
    }

    /**
     * Get message count
     */
    public function getMessageCount(): int
    {
        return $this->messages()->count();
    }

    /**
     * Get latest message
     */
    public function getLatestMessage()
    {
        return $this->messages()->latest()->first();
    }

    /**
     * Get the number of days this ticket has been open.
     */
    public function getDaysOpenAttribute(): int
    {
        return (int) $this->created_at->diffInDays(now());
    }
}
