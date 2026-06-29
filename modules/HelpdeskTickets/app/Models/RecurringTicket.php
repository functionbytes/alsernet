<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringTicket extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_recurring_tickets';

    protected $fillable = [
        'name',
        'subject',
        'description',
        'category_id',
        'priority_id',
        'assignee_id',
        'frequency',
        'cron_expression',
        'next_run_at',
        'last_run_at',
        'is_active',
        'tickets_created',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'next_run_at' => 'datetime',
            'last_run_at' => 'datetime',
            'tickets_created' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    public function assignee(): BelongsTo
    {
        $instance = (new User)->setConnection(null);

        return $this->newBelongsTo(
            $instance->newQuery(),
            $this,
            'assignee_id',
            'id',
            'assignee'
        );
    }

    /**
     * Calculate the next run date based on frequency.
     */
    public function calculateNextRun(): Carbon
    {
        $base = $this->last_run_at ?? now();

        return match ($this->frequency) {
            'daily' => $base->copy()->addDay(),
            'weekly' => $base->copy()->addWeek(),
            'monthly' => $base->copy()->addMonth(),
            'custom' => $this->cron_expression
                ? $this->nextRunFromCron($this->cron_expression)
                : now()->addDay(),
            default => now()->addMinutes(15),
        };
    }

    /**
     * Parse a standard 5-part cron expression and return the next run time.
     */
    private function nextRunFromCron(string $expression): Carbon
    {
        $parts = explode(' ', trim($expression));

        if (count($parts) !== 5) {
            return now()->addDay();
        }

        [$minute, $hour, $day, $month, $weekday] = $parts;

        // Every hour: 0 * * * *
        if ($hour === '*' && $day === '*' && $month === '*' && $weekday === '*') {
            return now()->addHour()->setMinute((int) ($minute === '*' ? 0 : $minute));
        }

        // Daily at specific time: M H * * *
        if ($day === '*' && $month === '*' && $weekday === '*' && $hour !== '*') {
            return now()->addDay()->setTime((int) $hour, (int) ($minute === '*' ? 0 : $minute));
        }

        // Weekly on specific day: M H * * DOW
        if ($day === '*' && $month === '*' && $weekday !== '*') {
            return now()->next((int) $weekday)->setTime(
                (int) ($hour === '*' ? 9 : $hour),
                (int) ($minute === '*' ? 0 : $minute)
            );
        }

        return now()->addDay();
    }

    /**
     * Scope: recurring tickets that are due to run now.
     */
    public function scopeDueToRun(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now());
    }
}
