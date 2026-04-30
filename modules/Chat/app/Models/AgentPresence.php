<?php

namespace Modules\Chat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentPresence extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'chat_agent_presence';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'account_id',
        'status',
        'last_seen_at',
        'session_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    /**
     * Get the user that owns the presence.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update agent status to online.
     */
    public function markAsOnline(string $sessionId): void
    {
        $this->update([
            'status' => 'online',
            'last_seen_at' => now(),
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Update agent status to offline.
     */
    public function markAsOffline(): void
    {
        $this->update([
            'status' => 'offline',
            'last_seen_at' => now(),
            'session_id' => null,
        ]);
    }

    /**
     * Update agent status to away.
     */
    public function markAsAway(): void
    {
        $this->update([
            'status' => 'away',
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Update agent status to busy.
     */
    public function markAsBusy(): void
    {
        $this->update([
            'status' => 'busy',
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Update last seen timestamp.
     */
    public function heartbeat(): void
    {
        $this->update([
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Check if agent is online.
     */
    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    /**
     * Check if agent is available (online or away).
     */
    public function isAvailable(): bool
    {
        return in_array($this->status, ['online', 'away']);
    }

    /**
     * Scope to get only online agents.
     */
    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    /**
     * Scope to get available agents (online or away).
     */
    public function scopeAvailable($query)
    {
        return $query->whereIn('status', ['online', 'away']);
    }

    /**
     * Scope to get agents for a specific account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope to get agents who haven't been seen recently (5 minutes).
     */
    public function scopeStale($query)
    {
        return $query->where('last_seen_at', '<', now()->subMinutes(5));
    }
}
