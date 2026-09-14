<?php

namespace Modules\Supplier\Models\Automation;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Automation\AutomationDeadLetterQueueFactory;

class AutomationDeadLetterQueue extends Model
{
    use HasFactory;

    protected $table = 'supplier_automation_dead_letter_queue';

    protected $fillable = [
        'execution_id',
        'failure_reason',
        'error_details',
        'original_payload',
        'requires_action',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'error_details' => 'json',
        'original_payload' => 'json',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function newFactory(): AutomationDeadLetterQueueFactory
    {
        return AutomationDeadLetterQueueFactory::new();
    }

    /**
     * Relationship to execution
     */
    public function execution(): BelongsTo
    {
        return $this->belongsTo(AutomationExecution::class, 'execution_id');
    }

    /**
     * Relationship to resolver user
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Scope: Get unresolved items
     */
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope: Get resolved items
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->whereNotNull('resolved_at');
    }

    /**
     * Scope: Filter by failure reason
     */
    public function scopeWithFailureReason(Builder $query, string $reason): Builder
    {
        return $query->where('failure_reason', $reason);
    }

    /**
     * Scope: Filter by required action
     */
    public function scopeRequiringAction(Builder $query, string $action): Builder
    {
        return $query->where('requires_action', $action);
    }

    /**
     * Scope: Items requiring manual intervention
     */
    public function scopeNeedsIntervention(Builder $query): Builder
    {
        return $query->whereNull('resolved_at')
            ->whereIn('requires_action', ['retry', 'fix_config', 'contact_supplier']);
    }

    /**
     * Check if item is resolved
     */
    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * Check if item needs manual intervention
     */
    public function needsIntervention(): bool
    {
        return ! $this->isResolved() && in_array($this->requires_action, ['retry', 'fix_config', 'contact_supplier']);
    }

    /**
     * Mark as resolved
     */
    public function markAsResolved(?int $userId = null, ?string $notes = null): void
    {
        $this->update([
            'resolved_by' => $userId ?? auth()->id(),
            'resolved_at' => now(),
            'resolution_notes' => $notes ?? $this->resolution_notes,
        ]);
    }

    /**
     * Update required action
     */
    public function updateRequiredAction(string $action, ?string $notes = null): void
    {
        $updates = ['requires_action' => $action];

        if ($notes) {
            $updates['resolution_notes'] = $notes;
        }

        $this->update($updates);
    }

    /**
     * Create dead letter entry from execution
     */
    public static function createFromExecution(
        int $executionId,
        string $failureReason,
        array $errorDetails,
        ?array $originalPayload = null,
        string $requiredAction = 'none'
    ): self {
        return self::create([
            'execution_id' => $executionId,
            'failure_reason' => $failureReason,
            'error_details' => $errorDetails,
            'original_payload' => $originalPayload,
            'requires_action' => $requiredAction,
        ]);
    }

    /**
     * Get statistics
     */
    public static function getStatistics(int $days = 7): array
    {
        $since = now()->subDays($days);

        $items = self::where('created_at', '>=', $since)->get();

        return [
            'total' => $items->count(),
            'unresolved' => $items->whereNull('resolved_at')->count(),
            'resolved' => $items->whereNotNull('resolved_at')->count(),
            'by_reason' => [
                'max_retries' => $items->where('failure_reason', 'max_retries')->count(),
                'invalid_config' => $items->where('failure_reason', 'invalid_config')->count(),
                'source_gone' => $items->where('failure_reason', 'source_gone')->count(),
                'timeout' => $items->where('failure_reason', 'timeout')->count(),
                'manual' => $items->where('failure_reason', 'manual')->count(),
            ],
            'by_action' => [
                'retry' => $items->where('requires_action', 'retry')->count(),
                'fix_config' => $items->where('requires_action', 'fix_config')->count(),
                'contact_supplier' => $items->where('requires_action', 'contact_supplier')->count(),
                'skip' => $items->where('requires_action', 'skip')->count(),
                'none' => $items->where('requires_action', 'none')->count(),
            ],
            'needs_intervention' => $items->filter(fn ($item) => $item->needsIntervention())->count(),
        ];
    }

    /**
     * Get items requiring attention
     */
    public static function getItemsRequiringAttention(): Collection
    {
        return self::unresolved()
            ->whereIn('requires_action', ['retry', 'fix_config', 'contact_supplier'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Bulk resolve items
     */
    public static function bulkResolve(array $ids, ?int $userId = null, ?string $notes = null): int
    {
        return self::whereIn('id', $ids)
            ->whereNull('resolved_at')
            ->update([
                'resolved_by' => $userId ?? auth()->id(),
                'resolved_at' => now(),
                'resolution_notes' => $notes,
            ]);
    }
}
