<?php

namespace Modules\Supplier\Models\Automation;

use App\Models\User;
use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Automation\AutomationChainExecutionFactory;

class AutomationChainExecution extends Model
{
    protected $table = 'supplier_automation_chain_executions';

    use HasFactory, HasUid;

    protected $fillable = [
        'uid',
        'chain_id',
        'status',
        'current_stage',
        'completed_stages',
        'failed_stages',
        'execution_context',
        'stage_results',
        'started_at',
        'completed_at',
        'pending_approval_stage',
        'approval_requested_at',
        'approved_by',
        'approved_at',
        'triggered_by',
        'triggered_by_user',
    ];

    protected function casts(): array
    {
        return [
            'chain_id' => 'integer',
            'completed_stages' => 'array',
            'failed_stages' => 'array',
            'execution_context' => 'array',
            'stage_results' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'approval_requested_at' => 'datetime',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
            'triggered_by_user' => 'integer',
        ];
    }

    protected static function newFactory(): AutomationChainExecutionFactory
    {
        return AutomationChainExecutionFactory::new();
    }

    /**
     * Get the chain being executed.
     */
    public function chain(): BelongsTo
    {
        return $this->belongsTo(AutomationChain::class, 'chain_id');
    }

    /**
     * Get the user who approved this execution.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who triggered this execution.
     */
    public function triggeringUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user');
    }

    /**
     * Scope: Filter by status
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Get pending executions
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Get running executions
     */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope: Get paused executions
     */
    public function scopePaused(Builder $query): Builder
    {
        return $query->where('status', 'paused');
    }

    /**
     * Scope: Get executions waiting for approval
     */
    public function scopeWaitingApproval(Builder $query): Builder
    {
        return $query->where('status', 'waiting_approval');
    }

    /**
     * Scope: Get completed executions
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Get failed executions
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Get cancelled executions
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope: Get active executions (running or paused)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['running', 'paused', 'waiting_approval']);
    }

    /**
     * Start the execution
     */
    public function start(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Complete a stage
     */
    public function completeStage(string $stageId, array $result = []): void
    {
        $completedStages = $this->completed_stages ?? [];
        if (! in_array($stageId, $completedStages)) {
            $completedStages[] = $stageId;
        }

        $stageResults = $this->stage_results ?? [];
        $stageResults[$stageId] = $result;

        $this->update([
            'completed_stages' => $completedStages,
            'stage_results' => $stageResults,
        ]);
    }

    /**
     * Mark a stage as failed
     */
    public function failStage(string $stageId, array $error = []): void
    {
        $failedStages = $this->failed_stages ?? [];
        if (! in_array($stageId, $failedStages)) {
            $failedStages[] = $stageId;
        }

        $stageResults = $this->stage_results ?? [];
        $stageResults[$stageId] = array_merge(['error' => true], $error);

        $this->update([
            'failed_stages' => $failedStages,
            'stage_results' => $stageResults,
        ]);
    }

    /**
     * Move to next stage
     */
    public function moveToStage(string $stageId): void
    {
        $this->update([
            'current_stage' => $stageId,
        ]);
    }

    /**
     * Request approval for a stage
     */
    public function requestApproval(string $stageId): void
    {
        $this->update([
            'status' => 'waiting_approval',
            'pending_approval_stage' => $stageId,
            'approval_requested_at' => now(),
        ]);
    }

    /**
     * Approve the pending stage
     */
    public function approve(int $userId): void
    {
        $this->update([
            'status' => 'running',
            'pending_approval_stage' => null,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject the pending stage
     */
    public function reject(int $userId, string $reason = ''): void
    {
        $this->update([
            'status' => 'failed',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        if ($this->pending_approval_stage) {
            $this->failStage($this->pending_approval_stage, [
                'rejected' => true,
                'rejected_by' => $userId,
                'rejection_reason' => $reason,
            ]);
        }
    }

    /**
     * Pause the execution
     */
    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    /**
     * Resume the execution
     */
    public function resume(): void
    {
        $this->update(['status' => 'running']);
    }

    /**
     * Cancel the execution
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark execution as completed
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'current_stage' => null,
        ]);
    }

    /**
     * Mark execution as failed
     */
    public function fail(): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Check if stage is completed
     */
    public function isStageCompleted(string $stageId): bool
    {
        return in_array($stageId, $this->completed_stages ?? []);
    }

    /**
     * Check if stage is failed
     */
    public function isStageFailed(string $stageId): bool
    {
        return in_array($stageId, $this->failed_stages ?? []);
    }

    /**
     * Get result for a specific stage
     */
    public function getStageResult(string $stageId): ?array
    {
        return $this->stage_results[$stageId] ?? null;
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
     * Check if execution is paused
     */
    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    /**
     * Check if execution is waiting for approval
     */
    public function isWaitingApproval(): bool
    {
        return $this->status === 'waiting_approval';
    }

    /**
     * Check if execution is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if execution has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if execution is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get execution progress percentage
     */
    public function getProgress(): float
    {
        $totalStages = $this->chain->getTotalStages();
        if ($totalStages === 0) {
            return 0;
        }

        $completedCount = count($this->completed_stages ?? []);

        return round(($completedCount / $totalStages) * 100, 2);
    }

    /**
     * Get execution duration in seconds
     */
    public function getDuration(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();

        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Add context data to execution
     */
    public function addContext(string $key, mixed $value): void
    {
        $context = $this->execution_context ?? [];
        $context[$key] = $value;

        $this->update(['execution_context' => $context]);
    }

    /**
     * Get context value
     */
    public function getContext(string $key): mixed
    {
        return $this->execution_context[$key] ?? null;
    }
}
