<?php

namespace Modules\Mailing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Mailing\Traits\HasUid;

class CronjobSetting extends Model
{
    use HasFactory, HasUid;

    protected $table = 'mailing_cronjob_settings';

    /*
    |--------------------------------------------------------------------------
    | Fillable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uid',
        'user_id',
        'job_name',
        'description',
        'interval_minutes',
        'last_run_at',
        'next_run_at',
        'is_enabled',
        'max_attempts',
        'timeout_seconds',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'interval_minutes' => 'integer',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
            'is_enabled' => 'boolean',
            'max_attempts' => 'integer',
            'timeout_seconds' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns this cronjob setting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the cronjob is due to run.
     */
    public function isDue(): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        if ($this->next_run_at === null) {
            return true;
        }

        return now()->greaterThanOrEqualTo($this->next_run_at);
    }

    /**
     * Mark the cronjob as having just run.
     */
    public function markAsRun(): void
    {
        $this->update([
            'last_run_at' => now(),
            'next_run_at' => $this->calculateNextRun(),
        ]);
    }

    /**
     * Get the next scheduled run time.
     */
    public function nextRun(): ?\DateTime
    {
        return $this->next_run_at;
    }

    /**
     * Calculate the next run time based on interval.
     */
    protected function calculateNextRun(): \DateTime
    {
        return now()->addMinutes($this->interval_minutes ?? 60);
    }

    /**
     * Enable this cronjob.
     */
    public function enable(): void
    {
        $this->update([
            'is_enabled' => true,
            'next_run_at' => now(),
        ]);
    }

    /**
     * Disable this cronjob.
     */
    public function disable(): void
    {
        $this->update(['is_enabled' => false]);
    }

    /**
     * Reset the cronjob to initial state.
     */
    public function reset(): void
    {
        $this->update([
            'last_run_at' => null,
            'next_run_at' => now(),
        ]);
    }

    /**
     * Get the time until the next run.
     */
    public function getTimeUntilNextRun(): ?\DateInterval
    {
        if ($this->next_run_at === null) {
            return null;
        }

        return $this->next_run_at->diff(now());
    }
}
