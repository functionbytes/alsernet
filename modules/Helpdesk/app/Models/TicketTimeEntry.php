<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketTimeEntry extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_time_entries';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'description',
        'minutes',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
            'minutes' => 'integer',
        ];
    }

    /**
     * Get the ticket that owns this time entry.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who logged this time entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a human-readable duration string (e.g. "2h 30m", "45m", "1h").
     */
    public function getFormattedDurationAttribute(): string
    {
        $hours = intdiv($this->minutes, 60);
        $mins = $this->minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return "{$hours}h {$mins}m";
        }

        if ($hours > 0) {
            return "{$hours}h";
        }

        return "{$mins}m";
    }

    /**
     * Scope: filter entries by user.
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: filter entries within a date range.
     */
    public function scopeForPeriod(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('logged_at', [$from, $to]);
    }
}
