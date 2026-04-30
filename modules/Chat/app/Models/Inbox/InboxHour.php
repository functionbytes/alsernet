<?php

namespace Modules\Chat\Models\Inbox;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Accounts\Account;

class InboxHour extends Model
{
    protected $table = 'chat_inboxe_hours';

    protected $fillable = [
        'account_id',
        'inbox_id',
        'day_of_week',
        'open_time',
        'close_time',
        'is_enabled',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'day_of_week' => 'integer',
        ];
    }

    // Day of week constants
    public const SUNDAY = 0;

    public const MONDAY = 1;

    public const TUESDAY = 2;

    public const WEDNESDAY = 3;

    public const THURSDAY = 4;

    public const FRIDAY = 5;

    public const SATURDAY = 6;

    public static array $daysOfWeek = [
        self::SUNDAY => 'Sunday',
        self::MONDAY => 'Monday',
        self::TUESDAY => 'Tuesday',
        self::WEDNESDAY => 'Wednesday',
        self::THURSDAY => 'Thursday',
        self::FRIDAY => 'Friday',
        self::SATURDAY => 'Saturday',
    ];

    /**
     * Get the account that owns the business hours.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the inbox that owns the business hours (optional).
     */
    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class);
    }

    /**
     * Check if currently within business hours.
     */
    public function isWithinBusinessHours(?Carbon $time = null): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        $time = $time ?? Carbon::now($this->timezone);
        $currentDay = $time->dayOfWeek;

        if ($currentDay !== $this->day_of_week) {
            return false;
        }

        $currentTime = $time->format('H:i:s');

        return $currentTime >= $this->open_time && $currentTime <= $this->close_time;
    }

    /**
     * Get day name.
     */
    public function getDayNameAttribute(): string
    {
        return self::$daysOfWeek[$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Scope to filter by account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope to filter by inbox.
     */
    public function scopeForInbox($query, ?int $inboxId)
    {
        return $query->where('inbox_id', $inboxId);
    }

    /**
     * Scope to filter enabled only.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
