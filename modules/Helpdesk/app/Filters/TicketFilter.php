<?php

namespace Modules\Helpdesk\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Group;

class TicketFilter
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        // filled() en vez de has(): el <form> de filtros de Tickets manda
        // TODOS los campos a la vez (source/category/assignee/priority/
        // tag...), no solo el que el agente acaba de tocar — con has(), un
        // solo <select> vacío en "Todos" (name="priority" value="") ya
        // contaba como "hay que filtrar por priority=''", que no matchea
        // ningún ticket y dejaba la lista en 0 resultados. Bug real
        // encontrado al portar el modal de "Más filtros" al design system.
        return $query
            ->when(
                $this->request->filled('status') && $this->request->status !== 'all',
                fn ($q) => $this->applyStatus($q)
            )
            ->when(
                $this->request->filled('category') && $this->request->category !== 'all',
                fn ($q) => $this->applyCategory($q)
            )
            ->when(
                $this->request->filled('assignee') && $this->request->assignee !== 'all',
                fn ($q) => $this->applyAssignee($q)
            )
            ->when(
                $this->request->filled('group') && $this->request->group !== 'all',
                fn ($q) => $this->applyGroup($q)
            )
            ->when(
                $this->request->filled('priority') && $this->request->priority !== 'all',
                fn ($q) => $this->applyPriority($q)
            )
            ->when(
                $this->request->filled('source') && $this->request->source !== 'all',
                fn ($q) => $this->applySource($q)
            )
            ->when(
                $this->request->filled('sla_status'),
                fn ($q) => $this->applySlaStatus($q)
            )
            ->when(
                $this->request->filled('created_from') || $this->request->filled('created_to'),
                fn ($q) => $this->applyCreatedRange($q)
            )
            ->when(
                $this->request->filled('search'),
                fn ($q) => $this->applySearch($q)
            )
            ->when(
                true,
                fn ($q) => $this->applyArchived($q)
            );
    }

    public function applyViewFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $key => $value) {
            if (empty($value) || $value === 'all') {
                continue;
            }

            match ($key) {
                'status_id' => $query->where('status_id', $value),
                'category_id' => $query->where('category_id', $value),
                'assignee' => $value === 'mine'
                    ? $query->where('assignee_id', auth()->id())
                    : ($value === 'unassigned'
                        ? $query->whereNull('assignee_id')
                        : $query->where('assignee_id', $value)),
                'group' => (function () use ($query, $value) {
                    $group = Group::find($value);
                    if ($group) {
                        $userIds = $group->users()->pluck('users.id');
                        $query->whereIn('assignee_id', $userIds);
                    }
                })(),
                'priority' => $query->where('priority', $value),
                'source' => $query->where('source', $value),
                'is_open' => $query->whereHas('status', fn ($q) => $q->where('is_open', (bool) $value)),
                'is_archived' => $query->where('is_archived', (bool) $value),
                'sla_breach' => (bool) $value ? $query->slaBreach() : null,
                default => null,
            };
        }
    }

    protected function applyStatus(Builder $query): Builder
    {
        return $query->where('status_id', $this->request->status);
    }

    protected function applyCategory(Builder $query): Builder
    {
        return $query->where('category_id', $this->request->category);
    }

    protected function applyAssignee(Builder $query): Builder
    {
        // El frontend de Tickets manda 'me' (ver _filters-modal.blade.php /
        // index.blade.php); 'mine' se acepta como alias por compatibilidad
        // con otros consumidores históricos de este mismo valor.
        if (in_array($this->request->assignee, ['me', 'mine'], true)) {
            return $query->where('assignee_id', auth()->id());
        }

        if ($this->request->assignee === 'unassigned') {
            return $query->whereNull('assignee_id');
        }

        return $query->where('assignee_id', $this->request->assignee);
    }

    protected function applyGroup(Builder $query): Builder
    {
        $group = Group::find($this->request->group);

        if ($group) {
            $userIds = $group->users()->pluck('users.id');
            $query->whereIn('assignee_id', $userIds);
        }

        return $query;
    }

    protected function applyPriority(Builder $query): Builder
    {
        return $query->where('priority', $this->request->priority);
    }

    protected function applySource(Builder $query): Builder
    {
        return $query->where('source', $this->request->source);
    }

    protected function applySlaStatus(Builder $query): Builder
    {
        if ($this->request->sla_status === 'breach') {
            return $query->slaBreach();
        }

        // El modal de filtros manda 'warn' (mismo valor que Ticket::slaRowKind()
        // y tickets-app.js usan en todo el frontend); 'warning' se conserva
        // como alias por si algún otro consumidor ya lo usaba.
        if (in_array($this->request->sla_status, ['warn', 'warning'], true)) {
            return $query->slaWarning();
        }

        return $query;
    }

    protected function applyCreatedRange(Builder $query): Builder
    {
        $this->request->validate([
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date',
        ]);

        return $query
            ->when(
                $this->request->filled('created_from'),
                fn ($q) => $q->whereDate('created_at', '>=', $this->request->created_from)
            )
            ->when(
                $this->request->filled('created_to'),
                fn ($q) => $q->whereDate('created_at', '<=', $this->request->created_to)
            );
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->search($this->request->search);
    }

    protected function applyArchived(Builder $query): Builder
    {
        if ($this->request->has('archived')) {
            return $query->where('is_archived', (bool) $this->request->archived);
        }

        return $query->where('is_archived', false);
    }
}
