<?php

namespace Modules\Helpdesk\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ConversationFilter
{
    /** @var array<string, mixed> Filters of the active view, remembered so apply()'s base scope doesn't fight them. */
    protected array $viewFilters = [];

    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        return $query
            ->when(
                $this->request->has('status') && $this->request->status !== 'all',
                fn ($q) => $this->applyStatusValue($q, $this->request->status)
            )
            ->when(
                $this->request->has('assignee') && $this->request->assignee !== 'all',
                fn ($q) => $this->applyAssigneeValue($q, $this->request->assignee)
            )
            ->when(
                $this->request->has('group') && $this->request->group !== 'all',
                fn ($q) => $q->where('group_id', $this->request->group)
            )
            ->when(
                $this->request->has('priority') && $this->request->priority !== 'all',
                fn ($q) => $q->where('priority', $this->request->priority)
            )
            ->when(
                $this->request->has('search') && ! empty($this->request->search),
                fn ($q) => $this->applySearchValue($q, $this->request->search)
            )
            ->when(
                $this->request->filled('channel'),
                fn ($q) => $q->where('channel', $this->request->input('channel'))
            )
            ->when(
                $this->request->filled('inbox'),
                fn ($q) => $q->where('inbox_id', (int) $this->request->input('inbox'))
            )
            ->when(
                true,
                fn ($q) => $this->applyArchived($q)
            );
    }

    /**
     * Apply a saved/seeded view's stored filters.
     *
     * Handles both vocabularies: the seeded views use is_open / is_archived /
     * status_id / snoozed, while user-saved views store the raw URL params
     * (status / archived / channel / inbox / tag / search / quick chips). Two
     * rules keep it correct:
     *
     *  - false/0 are meaningful (a "closed only" view stores is_open=false), so
     *    they must NOT be treated as "empty" and skipped.
     *  - an explicit request param on the same dimension wins over the (base)
     *    view — otherwise the default view's is_open=true / is_archived=false
     *    silently masked an explicit ?archived=1 or a closed-status filter.
     *
     * @param  array<string, mixed>  $filters
     */
    public function applyViewFilters(Builder $query, array $filters): void
    {
        // Remembered so apply()'s "hide archived" base scope yields to a view
        // that already constrains archived (e.g. a saved "archived" view).
        $this->viewFilters = $filters;

        foreach ($filters as $key => $value) {
            if ($this->isBlank($value) || $this->requestOverrides($key)) {
                continue;
            }

            match ($key) {
                // Seeded-view vocabulary.
                'status_id' => $query->where('status_id', $value),
                'snoozed' => $this->toBool($value) ? $query->snoozed() : null,
                'is_open' => $query->whereHas('status', fn ($q) => $q->where('is_open', $this->toBool($value))),
                'is_archived' => $query->where('is_archived', $this->toBool($value)),
                // Shared.
                'assignee' => $this->applyAssigneeValue($query, $value),
                'group' => $query->where('group_id', $value),
                'priority' => $query->where('priority', $value),
                // User-saved (raw URL) vocabulary.
                'status' => $this->applyStatusValue($query, (string) $value),
                'archived' => $query->where('is_archived', $this->toBool($value)),
                'channel' => $query->where('channel', $value),
                'inbox' => $query->where('inbox_id', (int) $value),
                'tag' => $query->whereHas('conversationTags', fn ($t) => $t->where('helpdesk_conversation_tags.id', (int) $value)),
                'search' => $this->applySearchValue($query, (string) $value),
                'urgent' => $query->where('priority', 'urgent'),
                'mine' => $query->where('assignee_id', auth()->id()),
                'unread' => $query->whereDoesntHave('reads', fn ($r) => $r->where('user_id', auth()->id())),
                'vip' => $query->whereHas('customer', fn ($c) => $c->where('total_conversations', '>=', 5)),
                default => null,
            };
        }
    }

    protected function applyStatusValue(Builder $query, string $status): Builder
    {
        if ($status === 'snoozed') {
            return $query->snoozed();
        }

        // Non-numeric status keywords (the sidebar "En espera" link sends
        // status=pending) must be resolved by name — the status_id column is an
        // integer, so where('status_id', 'pending') matched nothing and the view
        // came up empty even though its counter (which filters by the "Esperando"
        // status name) showed a count. Keep filter and counter consistent.
        if (! is_numeric($status)) {
            $name = ['pending' => 'Esperando'][$status] ?? $status;

            return $query->whereHas('status', fn ($q) => $q->where('name', $name));
        }

        return $query->where('status_id', $status);
    }

    protected function applyAssigneeValue(Builder $query, mixed $assignee): Builder
    {
        if ($assignee === 'mine') {
            return $query->where('assignee_id', auth()->id());
        }

        if ($assignee === 'unassigned') {
            return $query->whereNull('assignee_id');
        }

        return $query->where('assignee_id', $assignee);
    }

    protected function applySearchValue(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('subject', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($c) => $c
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));

            // A bare number also matches the conversation id (agents paste it).
            if (ctype_digit($search)) {
                $q->orWhere('helpdesk_conversations.id', (int) $search);
            }
        });
    }

    protected function applyArchived(Builder $query): Builder
    {
        // La papelera (view=deleted) muestra TODO lo eliminado, archivado o no:
        // el base scope "ocultar archivadas" no debe enmascarar la papelera.
        if ($this->request->input('view') === 'deleted') {
            return $query;
        }

        if ($this->request->has('archived')) {
            return $query->where('is_archived', $this->toBool($this->request->archived));
        }

        // Base scope hides archived — unless the active view already constrains
        // it (a saved "archived" view would otherwise be masked to zero results).
        if (array_key_exists('is_archived', $this->viewFilters) || array_key_exists('archived', $this->viewFilters)) {
            return $query;
        }

        return $query->where('is_archived', false);
    }

    /**
     * Whether an explicit request param overrides a view filter on the same
     * dimension, so the (base/default) view does not mask the user's intent.
     */
    protected function requestOverrides(string $viewKey): bool
    {
        $r = $this->request;

        return match ($viewKey) {
            'is_archived', 'archived' => $r->has('archived') || $r->filled('view'),
            'is_open', 'status_id', 'status', 'snoozed' => $r->has('status') || $r->boolean('archived'),
            'assignee', 'mine' => ($r->has('assignee') && $r->assignee !== 'all') || $r->boolean('mine'),
            'group' => $r->has('group') && $r->group !== 'all',
            'priority', 'urgent' => ($r->has('priority') && $r->priority !== 'all') || $r->boolean('urgent'),
            'channel' => $r->filled('channel'),
            'inbox' => $r->filled('inbox'),
            'tag' => $r->filled('tag'),
            'search' => $r->filled('search'),
            default => false,
        };
    }

    private function isBlank(mixed $value): bool
    {
        // Unlike empty(), false/0/'0' are kept — they are meaningful for the
        // boolean view filters (is_open=false = "closed only").
        return $value === null || $value === '' || $value === 'all' || (is_array($value) && $value === []);
    }

    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
