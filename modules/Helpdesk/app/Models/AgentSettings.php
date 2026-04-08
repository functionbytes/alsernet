<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSettings extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_agent_settings';

    protected $fillable = [
        'user_id',
        'assignment_limit',
        'accepts_conversations',
        'working_hours',
    ];

    protected $casts = [
        'assignment_limit' => 'integer',
        'working_hours' => 'array',
    ];

    /**
     * Get the user that owns the agent backups.
     * Note: User model is in the default connection, not helpdesk
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)
            ->setConnection(config('database.default'));
    }

    /**
     * Create default backups for an agent.
     */
    public static function newFromDefault(): self
    {
        return new self([
            'assignment_limit' => 0,
            'accepts_conversations' => 'yes',
            'working_hours' => null,
        ]);
    }

    /**
     * Check if agent accepts conversations right now.
     */
    public function acceptsConversationsNow(): bool
    {
        if ($this->accepts_conversations === 'no') {
            return false;
        }

        if ($this->accepts_conversations === 'yes') {
            return true;
        }

        // Check working hours
        if ($this->accepts_conversations === 'working_hours' && $this->working_hours) {
            $now = now();
            $dayOfWeek = strtolower($now->format('l')); // monday, tuesday, etc.

            if (! isset($this->working_hours[$dayOfWeek])) {
                return false;
            }

            $hours = $this->working_hours[$dayOfWeek];

            if (! $hours['enabled'] ?? false) {
                return false;
            }

            $currentTime = $now->format('H:i');

            return $currentTime >= ($hours['start'] ?? '00:00')
                && $currentTime <= ($hours['end'] ?? '23:59');
        }

        return false;
    }

    /**
     * Check if agent has reached assignment limit.
     */
    public function hasReachedLimit(): bool
    {
        if ($this->assignment_limit === 0) {
            return false; // Unlimited
        }

        $activeConversations = $this->user
            ->conversations()
            ->whereIn('status', ['open', 'pending'])
            ->count();

        return $activeConversations >= $this->assignment_limit;
    }
}
