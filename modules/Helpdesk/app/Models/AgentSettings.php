<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSettings extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_agent_settings';

    public const PRESENCE_AVAILABLE = 'available';

    public const PRESENCE_BUSY = 'busy';

    public const PRESENCE_AWAY = 'away';

    public const PRESENCE_OFFLINE = 'offline';

    public const PRESENCE_STATES = [
        self::PRESENCE_AVAILABLE,
        self::PRESENCE_BUSY,
        self::PRESENCE_AWAY,
        self::PRESENCE_OFFLINE,
    ];

    protected $fillable = [
        'user_id',
        'accepts_conversations',
        'working_hours',
        'is_available',
        'max_concurrent_conversations',
        'auto_assign',
        'vacation_until',
        'current_open_count',
        'preferences',
        'languages',
        'presence_state',
        'presence_changed_at',
        'last_heartbeat_at',
    ];

    protected function casts(): array
    {
        return [
            'max_concurrent_conversations' => 'integer',
            'current_open_count' => 'integer',
            'working_hours' => 'array',
            'preferences' => 'array',
            'languages' => 'array',
            'is_available' => 'boolean',
            'auto_assign' => 'boolean',
            'vacation_until' => 'datetime',
            'presence_changed_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function newFromDefault(): self
    {
        return new self([
            'accepts_conversations' => 'yes',
            'is_available' => true,
            'max_concurrent_conversations' => 10,
            'auto_assign' => true,
            'working_hours' => null,
        ]);
    }

    /**
     * Whether the agent is currently on vacation.
     */
    public function isOnVacation(): bool
    {
        return $this->vacation_until !== null && $this->vacation_until->isFuture();
    }

    /**
     * Whether the agent can receive new assignment right now.
     */
    public function canReceiveAssignment(): bool
    {
        if (! $this->is_available) {
            return false;
        }

        if ($this->isOnVacation()) {
            return false;
        }

        if (! ($this->auto_assign ?? true)) {
            return false;
        }

        $maxOpen = $this->max_concurrent_conversations ?? 10;
        if ($maxOpen > 0 && ($this->current_open_count ?? 0) >= $maxOpen) {
            return false;
        }

        return true;
    }

    /**
     * Whether the agent speaks the given language. Comparison is on the
     * primary ISO-639 subtag, so "es" matches "es-ES"/"es_MX" and vice versa.
     */
    public function speaksLanguage(string $language): bool
    {
        $language = self::normalizeLanguage($language);

        if ($language === '') {
            return false;
        }

        foreach ((array) ($this->languages ?? []) as $spoken) {
            if (is_string($spoken) && self::normalizeLanguage($spoken) === $language) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lowercase primary subtag of a language tag ("es-ES" → "es", "" → "").
     */
    public static function normalizeLanguage(string $language): string
    {
        $primary = preg_split('/[-_]/', strtolower(trim($language)))[0] ?? '';

        return preg_match('/^[a-z]{2,3}$/', $primary) === 1 ? $primary : '';
    }

    public function acceptsConversationsNow(): bool
    {
        if ($this->accepts_conversations === 'no') {
            return false;
        }

        if ($this->accepts_conversations === 'yes') {
            return true;
        }

        if ($this->accepts_conversations === 'working_hours' && $this->working_hours) {
            $now = now();
            $dayOfWeek = strtolower($now->format('l'));

            if (! isset($this->working_hours[$dayOfWeek])) {
                return false;
            }

            $hours = $this->working_hours[$dayOfWeek];

            if (! ($hours['enabled'] ?? false)) {
                return false;
            }

            $currentTime = $now->format('H:i');

            return $currentTime >= ($hours['start'] ?? '00:00')
                && $currentTime <= ($hours['end'] ?? '23:59');
        }

        return false;
    }

    public function hasReachedLimit(): bool
    {
        $limit = $this->max_concurrent_conversations ?? 0;

        if ($limit === 0) {
            return false;
        }

        $activeCount = $this->current_open_count
            ?? Conversation::query()
                ->where('assignee_id', $this->user_id)
                ->whereHas('status', fn ($q) => $q->where('is_open', true))
                ->count();

        return $activeCount >= $limit;
    }
}
