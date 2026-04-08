<?php

namespace Modules\Helpdesk\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Group;

class ConversationFilter
{
    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        return $query
            ->when(
                $this->request->has('status') && $this->request->status !== 'all',
                fn ($q) => $this->applyStatus($q)
            )
            ->when(
                $this->request->has('assignee') && $this->request->assignee !== 'all',
                fn ($q) => $this->applyAssignee($q)
            )
            ->when(
                $this->request->has('group') && $this->request->group !== 'all',
                fn ($q) => $this->applyGroup($q)
            )
            ->when(
                $this->request->has('priority') && $this->request->priority !== 'all',
                fn ($q) => $this->applyPriority($q)
            )
            ->when(
                $this->request->has('search') && ! empty($this->request->search),
                fn ($q) => $this->applySearch($q)
            )
            ->when(
                $this->request->filled('channel'),
                fn ($q) => $q->where('channel', $this->request->input('channel'))
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
                'is_open' => $query->whereHas('status', fn ($q) => $q->where('is_open', (bool) $value)),
                'is_archived' => $query->where('is_archived', (bool) $value),
                default => null,
            };
        }
    }

    protected function applyStatus(Builder $query): Builder
    {
        return $query->where('status_id', $this->request->status);
    }

    protected function applyAssignee(Builder $query): Builder
    {
        $userId = auth()->id();

        if ($this->request->assignee === 'mine') {
            return $query->where('assignee_id', $userId);
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

    protected function applySearch(Builder $query): Builder
    {
        $search = $this->request->search;

        return $query->where(function ($q) use ($search) {
            $q->where('subject', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        });
    }

    protected function applyArchived(Builder $query): Builder
    {
        if ($this->request->has('archived')) {
            return $query->where('is_archived', (bool) $this->request->archived);
        }

        return $query->where('is_archived', false);
    }
}
