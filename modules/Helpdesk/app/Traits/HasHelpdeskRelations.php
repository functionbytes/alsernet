<?php

namespace Modules\Helpdesk\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Group;

/**
 * Trait HasHelpdeskRelations
 *
 * Gestiona las relaciones del usuario con el sistema de helpdesk:
 * tickets, conversaciones, grupos de agentes, y configuraciones.
 */
trait HasHelpdeskRelations
{
    /**
     * Get user's tickets
     */
    public function tickets(): HasMany
    {
        return $this->hasMany('App\Models\Ticket\Ticket');
    }

    /**
     * Get the agent settings for this user
     */
    public function agentSettings(): HasOne
    {
        return $this->hasOne(AgentSettings::class);
    }

    /**
     * Get the groups that the user belongs to
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'helpdesk_group_user', 'user_id', 'helpdesk_group_id')
            ->withPivot('conversation_priority')
            ->withTimestamps();
    }

    /**
     * Get the conversations assigned to this user
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'assigned_to');
    }

    /**
     * Check if the user accepts conversations right now
     */
    public function acceptsConversations(): bool
    {
        return $this->agentSettings?->acceptsConversationsNow() ?? false;
    }
}
