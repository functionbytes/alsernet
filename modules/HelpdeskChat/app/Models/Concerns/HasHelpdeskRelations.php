<?php

namespace Modules\HelpdeskChat\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HelpdeskChat\Models\Accounts\Account;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Teams\Team;

/**
 * Trait HasHelpdeskRelations
 *
 * Adds helpdesk-specific relationships and methods to the User model.
 * This trait should be used in App\Models\User.
 */
trait HasHelpdeskRelations
{
    /**
     * Get the helpdesk account that owns the user.
     */
    public function helpdeskAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Alias for helpdeskAccount for backward compatibility.
     * This allows using $user->account instead of $user->helpdeskAccount.
     */
    public function account(): BelongsTo
    {
        return $this->helpdeskAccount();
    }

    /**
     * Get the account safely (returns null if not set).
     */
    public function getAccount(): ?Account
    {
        return $this->account;
    }

    /**
     * Check if user has a helpdesk account.
     */
    public function hasHelpdeskAccount(): bool
    {
        return $this->account_id !== null && $this->account !== null;
    }

    /**
     * Get conversations assigned to this user.
     */
    public function assignedConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'assignee_id');
    }

    /**
     * Get helpdesk teams that this user is a member of.
     */
    public function helpdeskTeams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'helpdesk_team_user', 'user_id', 'helpdesk_team_id')
            ->withPivot('is_lead', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get open conversations assigned to this user.
     */
    public function openConversations(): HasMany
    {
        return $this->assignedConversations()
            ->where('status', 'open')
            ->orWhere('status', 'pending');
    }

    /**
     * Get the user's conversation workload count.
     */
    public function getConversationWorkloadCount(): int
    {
        return $this->assignedConversations()
            ->whereIn('status', ['open', 'pending'])
            ->count();
    }

    /**
     * Check if user can be assigned more conversations.
     */
    public function canAcceptMoreConversations(int $maxWorkload = 10): bool
    {
        return $this->isAvailable() && $this->getConversationWorkloadCount() < $maxWorkload;
    }
}
