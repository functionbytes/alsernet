<?php

namespace Modules\Chat\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Chat\Models\AgentPresence;

class AgentPresenceService
{
    /**
     * Update agent presence status.
     */
    public function updateStatus(User $user, string $status, ?string $sessionId = null): AgentPresence
    {
        $presence = AgentPresence::firstOrCreate(
            ['user_id' => $user->id],
            [
                'account_id' => $user->account_id ?? abort(403, 'No account assigned'),
                'status' => 'offline',
            ]
        );

        $presence->update([
            'status' => $status,
            'last_seen_at' => now(),
            'session_id' => $sessionId,
        ]);

        return $presence;
    }

    /**
     * Mark agent as online.
     */
    public function markOnline(User $user, string $sessionId): AgentPresence
    {
        return $this->updateStatus($user, 'online', $sessionId);
    }

    /**
     * Mark agent as offline.
     */
    public function markOffline(User $user): AgentPresence
    {
        return $this->updateStatus($user, 'offline', null);
    }

    /**
     * Mark agent as away.
     */
    public function markAway(User $user): AgentPresence
    {
        return $this->updateStatus($user, 'away');
    }

    /**
     * Mark agent as busy.
     */
    public function markBusy(User $user): AgentPresence
    {
        return $this->updateStatus($user, 'busy');
    }

    /**
     * Update heartbeat for agent.
     */
    public function heartbeat(User $user): void
    {
        AgentPresence::where('user_id', $user->id)
            ->update(['last_seen_at' => now()]);
    }

    /**
     * Get online agents count for an account.
     */
    public function getOnlineAgentsCount(int $accountId): int
    {
        return AgentPresence::forAccount($accountId)
            ->online()
            ->count();
    }

    /**
     * Get available agents count for an account.
     */
    public function getAvailableAgentsCount(int $accountId): int
    {
        return AgentPresence::forAccount($accountId)
            ->available()
            ->count();
    }

    /**
     * Check if any agents are online for an account.
     */
    public function hasOnlineAgents(int $accountId): bool
    {
        return $this->getOnlineAgentsCount($accountId) > 0;
    }

    /**
     * Check if any agents are available for an account.
     */
    public function hasAvailableAgents(int $accountId): bool
    {
        return $this->getAvailableAgentsCount($accountId) > 0;
    }

    /**
     * Get all online agents for an account.
     */
    public function getOnlineAgents(int $accountId): Collection
    {
        return AgentPresence::forAccount($accountId)
            ->online()
            ->with('user')
            ->get();
    }

    /**
     * Get all available agents for an account.
     */
    public function getAvailableAgents(int $accountId): Collection
    {
        return AgentPresence::forAccount($accountId)
            ->available()
            ->with('user')
            ->get();
    }

    /**
     * Mark stale agents as offline (not seen in 5 minutes).
     */
    public function markStaleAgentsOffline(): int
    {
        return AgentPresence::stale()
            ->where('status', '!=', 'offline')
            ->update([
                'status' => 'offline',
                'session_id' => null,
            ]);
    }

    /**
     * Get agent presence status.
     */
    public function getAgentStatus(User $user): ?string
    {
        $presence = AgentPresence::where('user_id', $user->id)->first();

        return $presence?->status;
    }

    /**
     * Check if agent is online.
     */
    public function isAgentOnline(User $user): bool
    {
        return $this->getAgentStatus($user) === 'online';
    }

    /**
     * Check if agent is available.
     */
    public function isAgentAvailable(User $user): bool
    {
        $status = $this->getAgentStatus($user);

        return in_array($status, ['online', 'away']);
    }
}
