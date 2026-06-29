<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TicketView extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_views';

    protected $fillable = [
        'user_id',
        'ticket_id',
        'name',
        'description',
        'filters',
        'sort_by',
        'sort_direction',
        'is_default',
        'is_shared',
        'is_system',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'is_default' => 'boolean',
            'is_shared' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Auto-increment order for new views
        static::creating(function ($view) {
            if (is_null($view->order)) {
                $maxOrder = static::where('user_id', $view->user_id)->max('order') ?? 0;
                $view->order = $maxOrder + 1;
            }

            // Ensure only one default view per user
            if ($view->is_default) {
                static::where('user_id', $view->user_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });

        static::updating(function ($view) {
            // Ensure only one default view per user
            if ($view->is_default && $view->isDirty('is_default')) {
                static::where('user_id', $view->user_id)
                    ->where('id', '!=', $view->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get the user who owns this view
     * Note: User model is on default mysql connection, not helpdesk
     */
    public function user()
    {
        // Create instance with explicit mysql connection for cross-database relationship
        $user = new User;
        $user->setConnection('mysql');

        return $this->newBelongsTo(
            $user->newQuery(),
            $this,
            'user_id',
            'id',
            'user'
        );
    }

    /**
     * Scope to get views for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get shared views
     */
    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    /**
     * Scope to get views ordered by their sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Get the default view for a user
     */
    public static function getDefaultForUser($userId): ?self
    {
        return static::where('user_id', $userId)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Apply filters to a ticket query
     */
    public function applyFilters($query)
    {
        $filters = $this->filters ?? [];

        foreach ($filters as $key => $value) {
            match ($key) {
                'status_id' => $query->where('status_id', $value),
                'category_id' => $query->where('category_id', $value),
                'priority' => $query->where('priority', $value),
                'assignee_id' => $query->where('assignee_id', $value),
                'group_id' => $query->where('group_id', $value),
                'source' => $query->where('source', $value),
                'is_archived' => $query->where('is_archived', $value),
                'sla_breach' => $value ? $query->slaBreach() : $query,
                default => $query,
            };
        }

        if ($this->sort_by) {
            $query->orderBy($this->sort_by, $this->sort_direction ?? 'asc');
        }

        return $query;
    }

    /**
     * Reorder views based on an array of IDs.
     */
    public static function reorder(array $ids, $userId): void
    {
        foreach ($ids as $order => $id) {
            static::where('id', $id)
                ->where('user_id', $userId)
                ->update(['order' => $order + 1]);
        }
    }
}
