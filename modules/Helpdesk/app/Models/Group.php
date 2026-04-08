<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_groups';

    protected $fillable = [
        'name',
        'assignment_mode',
        'default',
    ];

    protected $casts = [
        'default' => 'boolean',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'pivot',
    ];

    /**
     * Get the users (agents) that belong to the group.
     */
    public function users(): BelongsToMany
    {
        // Since Group uses 'helpdesk' connection but User uses default connection,
        // we need to manually construct a cross-database relationship
        $defaultConnection = config('database.default');
        $defaultDatabase = config("database.connections.{$defaultConnection}.database");

        $relation = $this->belongsToMany(
            User::class,
            'helpdesk_group_user',
            'group_id',
            'user_id'
        )
            ->withPivot('conversation_priority')
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
        return $this->users()->wherePivot('conversation_priority', 'primary');
    }

    /**
     * Get agents with backup priority.
     */
    public function backupAgents()
    {
        return $this->users()->wherePivot('conversation_priority', 'backup');
    }

    /**
     * Find the default group.
     */
    public static function findDefault(): ?self
    {
        return static::where('default', true)->first();
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
        // Get agent with least active conversations
        return $agents->sortBy(function ($agent) {
            return $agent->conversations()
                ->whereIn('status', ['open', 'pending'])
                ->count();
        })->first();
    }

    /**
     * Searchable fields for Scout.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at?->timestamp ?? '_null',
            'updated_at' => $this->updated_at?->timestamp ?? '_null',
        ];
    }

    /**
     * Filterable fields.
     */
    public static function filterableFields(): array
    {
        return ['id', 'default', 'created_at', 'updated_at'];
    }
}
