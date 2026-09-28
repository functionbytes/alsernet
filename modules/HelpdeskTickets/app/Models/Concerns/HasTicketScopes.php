<?php

namespace Modules\HelpdeskTickets\Models\Concerns;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Models\TicketStatus;

trait HasTicketScopes
{
    /**
     * Scope: Get open tickets
     */
    public function scopeOpen($query)
    {
        $openIds = Cache::remember('helpdesk:open-status-ids', 3600, fn () => TicketStatus::where('is_open', true)->pluck('id')->toArray());

        return $query->whereIn('status_id', $openIds);
    }

    /**
     * Scope: tickets pospuestos (snooze activo — reaparecen en el futuro).
     */
    public function scopeSnoozed(Builder $query): Builder
    {
        return $query->whereNotNull('snoozed_until')->where('snoozed_until', '>', now());
    }

    /**
     * Scope: excluye los pospuestos de las colas activas (snooze vencido o nulo
     * cuenta como no-pospuesto). Úsalo en los listados por defecto.
     */
    public function scopeNotSnoozed(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('snoozed_until')->orWhere('snoozed_until', '<=', now()));
    }

    public function isSnoozed(): bool
    {
        return $this->snoozed_until !== null && $this->snoozed_until->isFuture();
    }

    /**
     * Scope: excluye tickets archivados — misma condición por defecto que
     * TicketFilter::applyArchived() SIEMPRE aplica a la query real del
     * listado de Gestión de tickets. Fuente única para que
     * TicketsCrudController::tabCounts() (badges de tabs/vistas) cuente
     * exactamente lo mismo que esa lista, sin duplicar la condición a mano
     * en dos sitios (bug real de QA: un ticket archivado seguía sumando en
     * los badges pese a no poder verse nunca en ninguna pestaña de esa
     * pantalla).
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope: Get closed tickets
     */
    public function scopeClosed($query)
    {
        $closedIds = Cache::remember('helpdesk:closed-status-ids', 3600, fn () => TicketStatus::where('is_open', false)->pluck('id')->toArray());

        return $query->whereIn('status_id', $closedIds);
    }

    /**
     * Scope: Get resolved tickets
     */
    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    /**
     * Scope: Get tickets by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Get tickets by source
     */
    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope: Get tickets with SLA breaches
     */
    public function scopeSlaBreach($query)
    {
        return $query->where(function ($q) {
            $q->where('sla_first_response_breached', true)
                ->orWhere('sla_next_response_breached', true)
                ->orWhere('sla_resolution_breached', true);
        });
    }

    /**
     * Scope: Get tickets near SLA breach (within threshold)
     */
    public function scopeSlaWarning($query, $minutesThreshold = 30)
    {
        $now = Carbon::now();
        $warningTime = $now->copy()->addMinutes($minutesThreshold);

        return $query->where(function ($q) use ($now, $warningTime) {
            $q->whereBetween('sla_first_response_due_at', [$now, $warningTime])
                ->orWhereBetween('sla_next_response_due_at', [$now, $warningTime])
                ->orWhereBetween('sla_resolution_due_at', [$now, $warningTime]);
        });
    }

    /**
     * Scope: Tickets past their SLA resolution due date
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('sla_resolution_due_at')
            ->where('sla_resolution_due_at', '<', now())
            ->whereNull('closed_at');
    }

    /**
     * Scope: Tickets needing attention — SLA breached OR unassigned
     */
    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query->whereNull('closed_at')
            ->where(function (Builder $q) {
                $q->where('sla_resolution_breached', true)
                    ->orWhereNull('assignee_id');
            });
    }

    /**
     * Scope: Filter by date range on created_at
     */
    public function scopeByDateRange(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope: Filter by group
     */
    public function scopeAssignedToGroup(Builder $query, int $groupId): Builder
    {
        return $query->where('group_id', $groupId);
    }

    /**
     * Scope: Search by ticket number, subject or customer name
     */
    /**
     * Scope: tickets que el agente puede ver. Sin helpdesk.tickets.manage,
     * solo los asignados a él, los de sus equipos y los que no tienen equipo
     * (la cola compartida de triaje). Única fuente para listado, API y /search.
     */
    public function scopeVisibleToAgent(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->hasPermissionTo('helpdesk.tickets.manage')) {
            return $query;
        }

        $groupIds = TicketGroup::idsForUser($user->id);

        return $query->where(function (Builder $q) use ($groupIds, $user) {
            $q->where('assignee_id', $user->id)
                ->orWhereNull('group_id');

            if ($groupIds !== []) {
                $q->orWhereIn('group_id', $groupIds);
            }
        });
    }

    public function scopeSearch($query, $term)
    {
        $query->where('ticket_number', 'like', "%{$term}%")
            ->orWhere('subject', 'like', "%{$term}%")
            // Email además del nombre: es lo que el agente suele tener a mano
            // cuando el cliente escribe desde otro canal.
            ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%"));

        // Texto de los mensajes (24-sep-2026). Usa el índice FULLTEXT que ya
        // tenía helpdesk_ticket_items para la búsqueda dentro del hilo; antes
        // desde el listado solo se encontraba por número, asunto o nombre.
        // Con menos de 3 caracteres MySQL no indexa la palabra y no aporta.
        if (mb_strlen(trim((string) $term)) >= 3 && in_array($query->getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $query->orWhereIn('id', fn ($sub) => $sub->select('ticket_id')
                ->from('helpdesk_ticket_items')
                ->whereNull('deleted_at')
                ->where('is_internal', false)
                ->whereRaw('MATCH(body, html_body) AGAINST (? IN NATURAL LANGUAGE MODE)', [$term]));
        }

        return $query;
    }
}
