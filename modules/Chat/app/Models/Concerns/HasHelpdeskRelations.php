<?php

namespace Modules\Chat\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Groups\Group;
use Modules\Chat\Models\Teams\Team;

/**
 * Trait HasHelpdeskRelations
 *
 * Adds chat-specific relationships and methods to the User model.
 * This trait should be used in App\Models\User.
 */
trait HasHelpdeskRelations
{
    /**
     * Get the chat accounts that belong to the user.
     */
    public function chatAccounts(): BelongsToMany
    {
        return $this->belongsToMany(
            Account::class,
            'chat_accounts_user',
            'user_id',
            'account_id'
        )->withTimestamps();
    }

    /**
     * Get the primary chat account for the user using HasOne through the pivot table.
     */
    public function account(): HasOne
    {
        return $this->hasOne(Account::class, 'id')
            ->whereIn('id', function ($query) {
                $query->select('account_id')
                    ->from('chat_accounts_user')
                    ->where('user_id', $this->id);
            });
    }

    /**
     * Get the account ID accessor (for backward compatibility).
     * Returns the first associated account ID.
     */
    public function getAccountIdAttribute(): ?int
    {
        if (isset($this->relations['account']) && $this->relations['account']) {
            return $this->relations['account']->id;
        }

        return once(fn () => DB::table('chat_accounts_user')
            ->where('user_id', $this->id)
            ->value('account_id'));
    }

    /**
     * Get the account safely (returns null if not set).
     */
    public function getAccount(): ?Account
    {
        return $this->chatAccounts()->first();
    }

    /**
     * Check if user has a chat account.
     */
    public function hasHelpdeskAccount(): bool
    {
        return $this->chatAccounts()->exists();
    }

    /**
     * Get conversations assigned to this user.
     */
    public function assignedConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'assignee_id');
    }

    /**
     * Get chat teams that this user is a member of.
     */
    public function chatTeams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'chat_team_user', 'user_id', 'team_id')
            ->withPivot('is_lead', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get chat groups that this user is a member of.
     */
    public function chatGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            Group::class,
            'chat_group_user',
            'user_id',
            'group_id'
        )->withTimestamps();
    }

    /**
     * Get open conversations assigned to this user.
     */
    public function openConversations(): HasMany
    {
        return $this->assignedConversations()
            ->whereHas('status', fn ($q) => $q->whereIn('slug', ['open', 'pending']));
    }

    /**
     * Get the user's conversation workload count.
     */
    public function getConversationWorkloadCount(): int
    {
        return $this->assignedConversations()
            ->whereHas('status', fn ($q) => $q->whereIn('slug', ['open', 'pending']))
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
