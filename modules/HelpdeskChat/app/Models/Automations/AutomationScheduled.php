<?php

namespace Modules\HelpdeskChat\Models\Automations;

use App\Models\Account;
use Cron\CronExpression;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationScheduled extends Model
{
    protected $table = 'helpdesk_automation_scheduled';

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'trigger_type',
        'conditions',
        'actions',
        'schedule_minutes',
        'schedule_cron',
        'last_run_at',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'actions' => 'array',
            'last_run_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    protected $attributes = [
        'conditions' => '[]',
        'actions' => '[]',
    ];

    /**
     * Get the account that owns the automation.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Check if the automation should run based on schedule.
     */
    public function shouldRun(): bool
    {
        if (! $this->active) {
            return false;
        }

        // Priority 1: schedule_minutes (simple interval-based scheduling)
        if ($this->schedule_minutes) {
            if (! $this->last_run_at) {
                return true;
            }

            return $this->last_run_at->addMinutes($this->schedule_minutes)->isPast();
        }

        // Priority 2: schedule_cron (cron expression-based scheduling)
        if ($this->schedule_cron) {
            try {
                $cron = new CronExpression($this->schedule_cron);

                // If never run before, check if current time matches cron
                if (! $this->last_run_at) {
                    return $cron->isDue();
                }

                // Get the next run time after last run
                $nextRunTime = $cron->getNextRunDate($this->last_run_at);

                // Should run if next run time has passed
                return $nextRunTime->isPast();
            } catch (\Exception $e) {
                // Invalid cron expression
                logger()->error('Invalid cron expression in automation', [
                    'automation_id' => $this->id,
                    'cron' => $this->schedule_cron,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        }

        // No schedule configured
        return false;
    }

    /**
     * Mark as run.
     */
    public function markAsRun(): void
    {
        $this->update(['last_run_at' => now()]);
    }

    /**
     * Validate a cron expression.
     */
    public static function isValidCronExpression(string $expression): bool
    {
        try {
            new CronExpression($expression);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get human-readable description of the schedule.
     */
    public function getScheduleDescriptionAttribute(): string
    {
        if ($this->schedule_minutes) {
            $hours = floor($this->schedule_minutes / 60);
            $minutes = $this->schedule_minutes % 60;

            if ($hours > 0 && $minutes > 0) {
                return "Every {$hours} hours and {$minutes} minutes";
            } elseif ($hours > 0) {
                return "Every {$hours} ".str('hour')->plural($hours);
            } else {
                return "Every {$minutes} ".str('minute')->plural($minutes);
            }
        }

        if ($this->schedule_cron) {
            try {
                $cron = new CronExpression($this->schedule_cron);

                return match ($this->schedule_cron) {
                    '* * * * *' => 'Every minute',
                    '0 * * * *' => 'Every hour',
                    '0 0 * * *' => 'Daily at midnight',
                    '0 9 * * *' => 'Daily at 9:00 AM',
                    '0 0 * * 0' => 'Weekly on Sunday',
                    '0 0 1 * *' => 'Monthly on the 1st',
                    default => "Cron: {$this->schedule_cron}",
                };
            } catch (\Exception $e) {
                return 'Invalid cron expression';
            }
        }

        return 'No schedule configured';
    }

    /**
     * Get the next scheduled run time.
     */
    public function getNextRunTimeAttribute(): ?\Carbon\Carbon
    {
        if ($this->schedule_minutes && $this->last_run_at) {
            return $this->last_run_at->copy()->addMinutes($this->schedule_minutes);
        }

        if ($this->schedule_cron) {
            try {
                $cron = new CronExpression($this->schedule_cron);
                $baseTime = $this->last_run_at ?? now();

                return \Carbon\Carbon::instance($cron->getNextRunDate($baseTime));
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}
