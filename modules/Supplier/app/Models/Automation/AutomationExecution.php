<?php

namespace Modules\Supplier\Models\Automation;

use App\Models\User;
use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Supplier\Database\Factories\Automation\AutomationExecutionFactory;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Supplier\Supplier;

class AutomationExecution extends Model
{
    use HasFactory;

    protected $table = 'supplier_automation_executions';

    protected static function newFactory(): AutomationExecutionFactory
    {
        return AutomationExecutionFactory::new();
    }

    use HasUid;

    protected $fillable = [
        'workflow_id',
        'supplier_id',
        'source_id',
        'external_execution_id',
        'status',
        'trigger_type',
        'input_payload',
        'output_data',
        'execution_context',
        'error_details',
        'duration_ms',
        'retry_count',
        'items_processed',
        'items_succeeded',
        'items_failed',
        'started_at',
        'completed_at',
        'triggered_by',
    ];

    protected $casts = [
        'input_payload' => 'json',
        'output_data' => 'json',
        'execution_context' => 'json',
        'error_details' => 'json',
        'duration_ms' => 'integer',
        'retry_count' => 'integer',
        'items_processed' => 'integer',
        'items_succeeded' => 'integer',
        'items_failed' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relationship to workflow
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(AutomationWorkflow::class, 'workflow_id');
    }

    /**
     * Relationship to supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Relationship to source
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    /**
     * Relationship to user who triggered the execution
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * Relationship to retry queue entry
     */
    public function retry(): HasOne
    {
        return $this->hasOne(AutomationRetryQueue::class, 'execution_id');
    }

    /**
     * Relationship to dead letter queue entry
     */
    public function deadLetter(): HasOne
    {
        return $this->hasOne(AutomationDeadLetterQueue::class, 'execution_id');
    }

    /**
     * Scope: Filter by status
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter pending executions
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Filter queued executions
     */
    public function scopeQueued(Builder $query): Builder
    {
        return $query->where('status', 'queued');
    }

    /**
     * Scope: Filter running executions
     */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope: Filter completed executions
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Filter failed executions
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Filter timeout executions
     */
    public function scopeTimeout(Builder $query): Builder
    {
        return $query->where('status', 'timeout');
    }

    /**
     * Scope: Filter cancelled executions
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope: Filter by trigger type
     */
    public function scopeTriggeredBy(Builder $query, string $triggerType): Builder
    {
        return $query->where('trigger_type', $triggerType);
    }

    /**
     * Scope: Filter by workflow
     */
    public function scopeForWorkflow(Builder $query, int $workflowId): Builder
    {
        return $query->where('workflow_id', $workflowId);
    }

    /**
     * Scope: Filter by supplier
     */
    public function scopeForSupplier(Builder $query, int $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Scope: Filter by source
     */
    public function scopeForSource(Builder $query, int $sourceId): Builder
    {
        return $query->where('source_id', $sourceId);
    }

    /**
     * Scope: Filter active executions (running or queued)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['running', 'queued']);
    }

    /**
     * Scope: Filter finished executions (completed, failed, timeout, cancelled)
     */
    public function scopeFinished(Builder $query): Builder
    {
        return $query->whereIn('status', ['completed', 'failed', 'timeout', 'cancelled']);
    }

    /**
     * Check if execution is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if execution is running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if execution is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if execution failed
     */
    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'timeout']);
    }

    /**
     * Check if execution is finished
     */
    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'timeout', 'cancelled']);
    }

    /**
     * Mark execution as queued
     */
    public function markAsQueued(): void
    {
        $this->update(['status' => 'queued']);
    }

    /**
     * Mark execution as running
     */
    public function markAsRunning(?string $externalExecutionId = null): void
    {
        $data = [
            'status' => 'running',
            'started_at' => now(),
        ];

        if ($externalExecutionId) {
            $data['external_execution_id'] = $externalExecutionId;
        }

        $this->update($data);
    }

    /**
     * Mark execution as completed
     */
    public function markAsCompleted(?array $outputData = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'output_data' => $outputData,
            'duration_ms' => $this->started_at ? now()->diffInMilliseconds($this->started_at) : null,
        ]);

        $this->workflow->incrementSuccessful();
    }

    /**
     * Mark execution as failed
     */
    public function markAsFailed(?array $errorDetails = null): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_details' => $errorDetails,
            'duration_ms' => $this->started_at ? now()->diffInMilliseconds($this->started_at) : null,
        ]);

        $this->workflow->incrementFailed();
    }

    /**
     * Mark execution as timeout
     */
    public function markAsTimeout(?array $errorDetails = null): void
    {
        $this->update([
            'status' => 'timeout',
            'completed_at' => now(),
            'error_details' => $errorDetails ?? ['error' => 'Execution timeout'],
            'duration_ms' => $this->started_at ? now()->diffInMilliseconds($this->started_at) : null,
        ]);

        $this->workflow->incrementFailed();
    }

    /**
     * Mark execution as cancelled
     */
    public function markAsCancelled(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'completed_at' => now(),
            'error_details' => $reason ? ['reason' => $reason] : null,
            'duration_ms' => $this->started_at ? now()->diffInMilliseconds($this->started_at) : null,
        ]);
    }

    /**
     * Increment retry count
     */
    public function incrementRetryCount(): void
    {
        $this->increment('retry_count');
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        if (! $this->items_processed || $this->items_processed === 0) {
            return 0.0;
        }

        return round(($this->items_succeeded / $this->items_processed) * 100, 2);
    }

    /**
     * Get failure rate percentage
     */
    public function getFailureRateAttribute(): float
    {
        if (! $this->items_processed || $this->items_processed === 0) {
            return 0.0;
        }

        return round(($this->items_failed / $this->items_processed) * 100, 2);
    }

    /**
     * Get duration in seconds
     */
    public function getDurationInSecondsAttribute(): ?float
    {
        if (! $this->duration_ms) {
            return null;
        }

        return round($this->duration_ms / 1000, 2);
    }

    /**
     * Create a new execution
     */
    public static function createExecution(
        int $workflowId,
        ?int $supplierId = null,
        ?int $sourceId = null,
        string $triggerType = 'manual',
        ?array $inputPayload = null,
        ?array $executionContext = null,
        ?int $triggeredBy = null
    ): self {
        return self::create([
            'workflow_id' => $workflowId,
            'supplier_id' => $supplierId,
            'source_id' => $sourceId,
            'status' => 'pending',
            'trigger_type' => $triggerType,
            'input_payload' => $inputPayload,
            'execution_context' => $executionContext,
            'triggered_by' => $triggeredBy,
            'retry_count' => 0,
        ]);
    }
}
