<?php

namespace Modules\Supplier\Models\Automation;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Automation\AutomationRetryQueueFactory;

class AutomationRetryQueue extends Model
{
    use HasFactory;

    protected $table = 'supplier_automation_retry_queue';

    protected $fillable = [
        'execution_id',
        'attempt_number',
        'max_attempts',
        'retry_at',
        'retry_strategy',
        'last_error',
        'status',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'max_attempts' => 'integer',
        'retry_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function newFactory(): AutomationRetryQueueFactory
    {
        return AutomationRetryQueueFactory::new();
    }

    /**
     * Relationship to execution
     */
    public function execution(): BelongsTo
    {
        return $this->belongsTo(AutomationExecution::class, 'execution_id');
    }

    /**
     * Scope: Get pending retries
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Get exhausted retries
     */
    public function scopeExhausted(Builder $query): Builder
    {
        return $query->where('status', 'exhausted');
    }

    /**
     * Scope: Get succeeded retries
     */
    public function scopeSucceeded(Builder $query): Builder
    {
        return $query->where('status', 'succeeded');
    }

    /**
     * Scope: Get retries ready to process
     */
    public function scopeReadyToRetry(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->where('retry_at', '<=', now());
    }

    /**
     * Scope: Filter by strategy
     */
    public function scopeWithStrategy(Builder $query, string $strategy): Builder
    {
        return $query->where('retry_strategy', $strategy);
    }

    /**
     * Check if retry is exhausted
     */
    public function isExhausted(): bool
    {
        return $this->attempt_number >= $this->max_attempts;
    }

    /**
     * Check if retry is ready
     */
    public function isReady(): bool
    {
        return $this->status === 'pending' && $this->retry_at->isPast();
    }

    /**
     * Calculate next retry time
     */
    public function calculateNextRetryTime(): Carbon
    {
        $baseDelay = 60; // 60 seconds base
        $maxDelay = 900; // 15 minutes max

        $delay = match ($this->retry_strategy) {
            'immediate' => 0,
            'linear' => $baseDelay * $this->attempt_number,
            'exponential' => min($baseDelay * pow(2, $this->attempt_number - 1), $maxDelay),
            default => $baseDelay,
        };

        return now()->addSeconds($delay);
    }

    /**
     * Mark as retrying
     */
    public function markAsRetrying(): void
    {
        $this->update(['status' => 'retrying']);
    }

    /**
     * Mark as succeeded
     */
    public function markAsSucceeded(): void
    {
        $this->update(['status' => 'succeeded']);
    }

    /**
     * Mark as exhausted
     */
    public function markAsExhausted(): void
    {
        $this->update(['status' => 'exhausted']);
    }

    /**
     * Schedule next retry
     */
    public function scheduleNextRetry(string $error): ?self
    {
        // Check if exhausted
        if ($this->isExhausted()) {
            $this->markAsExhausted();

            return null;
        }

        // Create next retry
        return self::create([
            'execution_id' => $this->execution_id,
            'attempt_number' => $this->attempt_number + 1,
            'max_attempts' => $this->max_attempts,
            'retry_at' => $this->calculateNextRetryTime(),
            'retry_strategy' => $this->retry_strategy,
            'last_error' => $error,
            'status' => 'pending',
        ]);
    }

    /**
     * Create initial retry entry
     */
    public static function createForExecution(
        int $executionId,
        string $error,
        string $strategy = 'exponential',
        int $maxAttempts = 3
    ): self {
        $retry = new self([
            'execution_id' => $executionId,
            'attempt_number' => 1,
            'max_attempts' => $maxAttempts,
            'retry_strategy' => $strategy,
            'last_error' => $error,
            'status' => 'pending',
        ]);

        $retry->retry_at = $retry->calculateNextRetryTime();
        $retry->save();

        return $retry;
    }

    /**
     * Get retry statistics
     */
    public static function getStatistics(int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $retries = self::where('created_at', '>=', $since)->get();

        return [
            'total' => $retries->count(),
            'pending' => $retries->where('status', 'pending')->count(),
            'retrying' => $retries->where('status', 'retrying')->count(),
            'succeeded' => $retries->where('status', 'succeeded')->count(),
            'exhausted' => $retries->where('status', 'exhausted')->count(),
            'success_rate' => $retries->count() > 0
                ? round(($retries->where('status', 'succeeded')->count() / $retries->count()) * 100, 2)
                : 0,
        ];
    }
}
