<?php

namespace Modules\HelpdeskChat\Services;

use App\Models\SavedView;
use Illuminate\Database\Eloquent\Builder;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class SavedViewService
{
    /**
     * Apply saved view filters to a conversation query.
     */
    public function applyFilters(Builder $query, SavedView $view): Builder
    {
        $filters = $view->filters ?? [];

        // Status filter
        if (isset($filters['status']) && ! empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Priority filter
        if (isset($filters['priority']) && ! empty($filters['priority'])) {
            if (is_array($filters['priority'])) {
                $query->whereIn('priority', $filters['priority']);
            } else {
                $query->where('priority', $filters['priority']);
            }
        }

        // Assignee filter
        if (isset($filters['assignee'])) {
            if ($filters['assignee'] === 'unassigned') {
                $query->whereNull('assignee_id');
            } elseif ($filters['assignee'] === 'me') {
                $query->where('assignee_id', auth()->id());
            } elseif (is_numeric($filters['assignee'])) {
                $query->where('assignee_id', $filters['assignee']);
            } elseif (is_array($filters['assignee'])) {
                $query->whereIn('assignee_id', $filters['assignee']);
            }
        }

        // Team filter
        if (isset($filters['team_id']) && ! empty($filters['team_id'])) {
            if (is_array($filters['team_id'])) {
                $query->whereIn('team_id', $filters['team_id']);
            } else {
                $query->where('team_id', $filters['team_id']);
            }
        }

        // Inbox filter
        if (isset($filters['inbox_id']) && ! empty($filters['inbox_id'])) {
            if (is_array($filters['inbox_id'])) {
                $query->whereIn('inbox_id', $filters['inbox_id']);
            } else {
                $query->where('inbox_id', $filters['inbox_id']);
            }
        }

        // Labels filter
        if (isset($filters['labels']) && ! empty($filters['labels'])) {
            $labels = is_array($filters['labels']) ? $filters['labels'] : [$filters['labels']];
            $query->whereHas('labels', function ($q) use ($labels) {
                $q->whereIn('labels.id', $labels);
            });
        }

        // Date range filter
        if (isset($filters['date_range'])) {
            $range = $filters['date_range'];

            if (isset($range['start'])) {
                $query->where('created_at', '>=', $range['start']);
            }

            if (isset($range['end'])) {
                $query->where('created_at', '<=', $range['end']);
            }
        }

        // Last activity filter
        if (isset($filters['last_activity'])) {
            switch ($filters['last_activity']) {
                case 'today':
                    $query->whereDate('last_activity_at', today());
                    break;
                case 'yesterday':
                    $query->whereDate('last_activity_at', today()->subDay());
                    break;
                case 'last_7_days':
                    $query->where('last_activity_at', '>=', now()->subDays(7));
                    break;
                case 'last_30_days':
                    $query->where('last_activity_at', '>=', now()->subDays(30));
                    break;
                case 'older_than_30_days':
                    $query->where('last_activity_at', '<', now()->subDays(30));
                    break;
            }
        }

        // Contact filter
        if (isset($filters['contact_id']) && ! empty($filters['contact_id'])) {
            $query->where('contact_id', $filters['contact_id']);
        }

        // SLA filter
        if (isset($filters['sla_status'])) {
            switch ($filters['sla_status']) {
                case 'breached':
                    $query->where(function ($q) {
                        $q->where('first_response_sla_breached', true)
                            ->orWhere('resolution_sla_breached', true);
                    });
                    break;
                case 'at_risk':
                    $query->whereNotNull('sla_policy_id')
                        ->where('first_response_sla_breached', false)
                        ->where('resolution_sla_breached', false);
                    break;
                case 'compliant':
                    $query->whereNotNull('sla_policy_id')
                        ->where('first_response_sla_breached', false)
                        ->where('resolution_sla_breached', false);
                    break;
            }
        }

        // Waiting on filter
        if (isset($filters['waiting_on'])) {
            switch ($filters['waiting_on']) {
                case 'customer':
                    $query->where('waiting_since', '!=', null);
                    break;
                case 'agent':
                    $query->whereNull('waiting_since');
                    break;
            }
        }

        // Search query
        if (isset($filters['search']) && ! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('contact', function ($contactQuery) use ($search) {
                    $contactQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                })->orWhereHas('messages', function ($messageQuery) use ($search) {
                    $messageQuery->where('content', 'like', "%{$search}%");
                });
            });
        }

        // Apply sorting
        if ($view->sort_by) {
            $query->orderBy($view->sort_by, $view->sort_direction ?? 'desc');
        } else {
            // Default sort by last activity
            $query->orderBy('last_activity_at', 'desc');
        }

        return $query;
    }

    /**
     * Get conversation for a saved view with pagination.
     */
    public function getConversations(SavedView $view, int $perPage = 25)
    {
        $query = Conversation::whereHas('inbox', function ($q) use ($view) {
            $q->where('account_id', $view->account_id);
        })
            ->with(['contact', 'inbox', 'assignee', 'team', 'labels']);

        $query = $this->applyFilters($query, $view);

        return $query->paginate($perPage);
    }

    /**
     * Get count of conversation matching a saved view.
     */
    public function getCount(SavedView $view): int
    {
        $query = Conversation::whereHas('inbox', function ($q) use ($view) {
            $q->where('account_id', $view->account_id);
        });

        $query = $this->applyFilters($query, $view);

        return $query->count();
    }

    /**
     * Create a new saved view.
     */
    public function create(array $data, int $userId, int $accountId): SavedView
    {
        // Get next position
        $maxPosition = SavedView::forUser($userId)
            ->ofType($data['view_type'] ?? 'conversation')
            ->max('position') ?? 0;

        return SavedView::create([
            'account_id' => $accountId,
            'user_id' => $userId,
            'name' => $data['name'],
            'view_type' => $data['view_type'] ?? 'conversation',
            'filters' => $data['filters'] ?? [],
            'sort_by' => $data['sort_by'] ?? null,
            'sort_direction' => $data['sort_direction'] ?? 'desc',
            'is_shared' => $data['is_shared'] ?? false,
            'is_default' => $data['is_default'] ?? false,
            'position' => $maxPosition + 1,
        ]);
    }

    /**
     * Update an existing saved view.
     */
    public function update(SavedView $view, array $data): SavedView
    {
        $view->update([
            'name' => $data['name'] ?? $view->name,
            'filters' => $data['filters'] ?? $view->filters,
            'sort_by' => $data['sort_by'] ?? $view->sort_by,
            'sort_direction' => $data['sort_direction'] ?? $view->sort_direction,
            'is_shared' => $data['is_shared'] ?? $view->is_shared,
            'is_default' => $data['is_default'] ?? $view->is_default,
        ]);

        return $view->fresh();
    }

    /**
     * Reorder saved views.
     */
    public function reorder(array $viewIds, int $userId): void
    {
        foreach ($viewIds as $position => $viewId) {
            SavedView::where('id', $viewId)
                ->where('user_id', $userId)
                ->update(['position' => $position]);
        }
    }

    /**
     * Set a view as default for user.
     */
    public function setAsDefault(SavedView $view): void
    {
        // Unset all other defaults for this user and type
        SavedView::forUser($view->user_id)
            ->ofType($view->view_type)
            ->where('id', '!=', $view->id)
            ->update(['is_default' => false]);

        // Set this view as default
        $view->update(['is_default' => true]);
    }

    /**
     * Duplicate a saved view.
     */
    public function duplicate(SavedView $view, int $userId): SavedView
    {
        $maxPosition = SavedView::forUser($userId)
            ->ofType($view->view_type)
            ->max('position') ?? 0;

        return SavedView::create([
            'account_id' => $view->account_id,
            'user_id' => $userId,
            'name' => $view->name.' (Copy)',
            'view_type' => $view->view_type,
            'filters' => $view->filters,
            'sort_by' => $view->sort_by,
            'sort_direction' => $view->sort_direction,
            'is_shared' => false,
            'is_default' => false,
            'position' => $maxPosition + 1,
        ]);
    }
}
