<?php

namespace Modules\Backup\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class BackupSchedule extends Model
{
    protected $fillable = [
        'name',
        'enabled',
        'frequency',
        'scheduled_time',
        'days_of_week',
        'days_of_month',
        'custom_interval_hours',
        'backup_types',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'days_of_week' => 'array',
        'days_of_month' => 'array',
        'backup_types' => 'array',
        'custom_interval_hours' => 'integer',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    /**
     * Get scheduled time as Carbon object (for formatting)
     */
    public function getScheduledTimeAttribute($value)
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        // If it's a string like "14:30:00", parse it
        if (is_string($value)) {
            try {
                // Parse time and set to today so we can use format()
                return Carbon::createFromFormat('H:i:s', $value)
                    ->setDate(Carbon::now()->year, Carbon::now()->month, Carbon::now()->day);
            } catch (\Exception $e) {
                try {
                    return Carbon::createFromFormat('H:i', $value)
                        ->setDate(Carbon::now()->year, Carbon::now()->month, Carbon::now()->day);
                } catch (\Exception $e2) {
                    return Carbon::now(); // Fallback
                }
            }
        }

        return $value;
    }

    /**
     * Check if this schedule should run now
     */
    public function shouldRunNow(): bool
    {
        if (! $this->enabled) {
            return false;
        }

        $now = Carbon::now();
        // $this->scheduled_time is already a Carbon object from the accessor
        $scheduledTime = $this->scheduled_time->copy()
            ->setDate($now->year, $now->month, $now->day);

        // Check if current time matches scheduled time (within 1 minute tolerance)
        if (! $now->between(
            $scheduledTime->copy()->subMinute(),
            $scheduledTime->copy()->addMinute()
        )) {
            return false;
        }

        // Check frequency constraints
        switch ($this->frequency) {
            case 'daily':
                return true;

            case 'weekly':
                $daysOfWeek = $this->days_of_week ?? [];

                return in_array($now->dayOfWeek, $daysOfWeek);

            case 'monthly':
                $daysOfMonth = $this->days_of_month ?? [];

                return in_array($now->day, $daysOfMonth);

            case 'custom':
                // Para custom, verificar si pasó el intervalo desde el último run
                if (! $this->last_run_at) {
                    return true;
                }
                $lastRun = Carbon::parse($this->last_run_at);
                $hours = (int) ($this->custom_interval_hours ?? 24);

                return $now->diffInHours($lastRun) >= $hours;

            default:
                return false;
        }
    }

    /**
     * Mark this schedule as just executed
     */
    public function markAsRun(): void
    {
        $this->update([
            'last_run_at' => Carbon::now(),
            'next_run_at' => $this->calculateNextRun(),
        ]);
    }

    /**
     * Calculate next run time
     */
    public function calculateNextRun(): Carbon
    {
        $now = Carbon::now();
        // $this->scheduled_time is already a Carbon object from the accessor
        $scheduledTime = $this->scheduled_time->copy()
            ->setDate($now->year, $now->month, $now->day);

        switch ($this->frequency) {
            case 'daily':
                // If scheduled time has already passed today, set for tomorrow
                if ($scheduledTime->isPast()) {
                    return $scheduledTime->addDay();
                }

                return $scheduledTime;

            case 'weekly':
                $daysOfWeek = $this->days_of_week ?? [];
                $nextDate = $now->copy();
                do {
                    $nextDate->addDay();
                } while (! in_array($nextDate->dayOfWeek, $daysOfWeek));

                // Set the time from scheduled_time
                return $nextDate->setHours(
                    $this->scheduled_time->hour,
                    $this->scheduled_time->minute,
                    $this->scheduled_time->second
                );

            case 'monthly':
                $daysOfMonth = $this->days_of_month ?? [];
                $nextDate = $now->copy()->addMonth()->startOfMonth();
                do {
                    if (in_array($nextDate->day, $daysOfMonth)) {
                        return $nextDate->setHours(
                            $this->scheduled_time->hour,
                            $this->scheduled_time->minute,
                            $this->scheduled_time->second
                        );
                    }
                    $nextDate->addDay();
                } while ($nextDate->month == $now->addMonth()->month);

                return $now->copy()->addMonth();

            case 'custom':
                $hours = (int) ($this->custom_interval_hours ?? 24);

                return $now->copy()->addHours($hours);

            default:
                return $now->copy()->addDay();
        }
    }
}
