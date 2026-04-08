<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TicketGroup extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_groups';

    protected $fillable = [
        'name',
        'description',
        'assignment_mode',
        'is_default',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Auto-increment order for new groups
        static::creating(function ($group) {
            if (is_null($group->order)) {
                $maxOrder = static::max('order') ?? 0;
                $group->order = $maxOrder + 1;
            }
        });
    }

    /**
     * Get the users (agents) that belong to the group.
     * Cross-database relationship with User model.
     */
    public function users(): BelongsToMany
    {
        $defaultConnection = config('database.default');
        $defaultDatabase = config("database.connections.{$defaultConnection}.database");

        $relation = $this->belongsToMany(
            User::class,
            'helpdesk_ticket_group_user',
            'ticket_group_id',
            'user_id'
        )
            ->withPivot('priority')
            ->withTimestamps();

        // Override the query to use the correct database for users table
        $relation->getQuery()->from("{$defaultDatabase}.users");

        return $relation;
    }

    /**
     * Get agents with primary priority.
     */
    public function primaryAgents()
    {
        return $this->users()->wherePivot('priority', 'primary');
    }

    /**
     * Get agents with backup priority.
     */
    public function backupAgents()
    {
        return $this->users()->wherePivot('priority', 'backup');
    }

    /**
     * Get ticket categories linked to this group
     */
    public function ticketCategories()
    {
        return $this->belongsToMany(
            TicketCategory::class,
            'helpdesk_ticket_category_ticket_group',
            'ticket_group_id',
            'ticket_category_id'
        )->withPivot(['is_default', 'priority'])
            ->withTimestamps();
    }

    /**
     * Find the default group.
     */
    public static function findDefault(): ?self
    {
        return static::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get the next agent for assignment based on assignment mode.
     */
    public function getNextAgent(): ?User
    {
        $agents = $this->primaryAgents()
            ->with('agentSettings')
            ->get()
            ->filter(function ($agent) {
                return $agent->agentSettings
                    && $agent->agentSettings->acceptsConversationsNow()
                    && ! $agent->agentSettings->hasReachedLimit();
            });

        if ($agents->isEmpty()) {
            // Try backup agents
            $agents = $this->backupAgents()
                ->with('agentSettings')
                ->get()
                ->filter(function ($agent) {
                    return $agent->agentSettings
                        && $agent->agentSettings->acceptsConversationsNow()
                        && ! $agent->agentSettings->hasReachedLimit();
                });
        }

        if ($agents->isEmpty()) {
            return null;
        }

        return match ($this->assignment_mode) {
            'round_robin' => $this->getNextAgentRoundRobin($agents),
            'load_balanced' => $this->getNextAgentLoadBalanced($agents),
            default => $agents->first(),
        };
    }

    /**
     * Get next agent using round robin.
     */
    protected function getNextAgentRoundRobin($agents): User
    {
        // Simple implementation: get agent with oldest assignment
        return $agents->sortBy(function ($agent) {
            return $agent->pivot->created_at;
        })->first();
    }

    /**
     * Get next agent using load balancing.
     */
    protected function getNextAgentLoadBalanced($agents): User
    {
        // Get agent with least active tickets
        return $agents->sortBy(function ($agent) {
            return Ticket::where('assignee_id', $agent->id)
                ->whereHas('status', function ($q) {
                    $q->where('is_open', true);
                })
                ->count();
        })->first();
    }

    /**
     * Scope to get only active groups.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get groups ordered by their sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Reorder groups based on an array of IDs.
     */
    public static function reorder(array $ids): void
    {
        foreach ($ids as $order => $id) {
            static::where('id', $id)->update(['order' => $order + 1]);
        }
    }
}
