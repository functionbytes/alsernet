<?php

namespace Modules\Supplier\Models\Sync;

use App\Traits\HasUid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/**
 * Group related sync operations together
 *
 * Represents a batch of synchronization operations that should be processed together.
 * Supports batch-level retry logic, progress tracking, and grouping of related operations.
 *
 * @property int $id
 * @property string $uid ULID unique identifier
 * @property int|null $supplier_id Associated supplier ID
 * @property string $batch_name Human-readable batch name
 * @property string $sync_type Type of sync: 'product', 'category', 'price', 'provider'
 * @property string $status Status: 'pending', 'running', 'completed', 'failed', 'cancelled'
 * @property string $priority Priority level: 'low', 'normal', 'high', 'urgent'
 * @property int $batch_size Number of items per batch iteration
 * @property int $total_batches Total number of sub-batches
 * @property int $processed_batches Completed sub-batches
 * @property int $total_items Total items in this batch
 * @property int $processed_items Successfully processed items
 * @property int $failed_items Failed items
 * @property int $retry_attempt Current retry attempt
 * @property int $max_retries Maximum retry attempts allowed
 * @property Carbon|null $started_at When batch started
 * @property Carbon|null $completed_at When batch completed
 * @property Carbon|null $last_retry_at Last retry timestamp
 * @property float|null $duration_seconds Total execution time
 * @property string|null $triggered_by Trigger source: 'manual', 'scheduled', 'webhook'
 * @property string|null $trigger_details Additional trigger information
 * @property array|null $filter_criteria Filter criteria applied to batch
 * @property array|null $metadata Additional metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
use Modules\Supplier\Database\Factories\Sync\SyncBatchFactory;
use Modules\Supplier\Models\Supplier\Supplier;

class SyncBatch extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_sync_batches';

    protected $fillable = [
        'uid',
        'supplier_id',
        'batch_name',
        'sync_type',
        'status',
        'priority',
        'batch_size',
        'total_batches',
        'processed_batches',
        'total_items',
        'processed_items',
        'failed_items',
        'retry_attempt',
        'max_retries',
        'started_at',
        'completed_at',
        'last_retry_at',
        'duration_seconds',
        'triggered_by',
        'trigger_details',
        'filter_criteria',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'batch_size' => 'integer',
            'total_batches' => 'integer',
            'processed_batches' => 'integer',
            'total_items' => 'integer',
            'processed_items' => 'integer',
            'failed_items' => 'integer',
            'retry_attempt' => 'integer',
            'max_retries' => 'integer',
            'duration_seconds' => 'float',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_retry_at' => 'datetime',
            'filter_criteria' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the associated supplier
     */
    protected static function newFactory(): SyncBatchFactory
    {
        return SyncBatchFactory::new();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get all sync logs for this batch
     */
    public function logs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'batch_id');
    }

    /**
     * Get all failures in this batch
     */
    public function failures(): HasMany
    {
        return $this->hasMany(SyncFailure::class, 'batch_id');
    }

    /**
     * Get all sync statuses for this batch
     */
    public function statuses(): HasMany
    {
        return $this->hasMany(SyncStatus::class, 'batch_id');
    }

    /**
     * Calculate overall progress percentage.
     * Falls back to processed+failed as denominator when total_items is unknown (0).
     * Capped at 100 — un total_items desactualizado (menor a lo realmente
     * procesado) no debe producir un porcentaje sin sentido como "232%".
     * Ver exceedsEstimatedTotal() para detectar ese caso en la vista.
     */
    public function getProgressPercentageAttribute(): float
    {
        $total = $this->total_items > 0
            ? $this->total_items
            : ($this->processed_items + $this->failed_items);

        if ($total === 0) {
            return 0.0;
        }

        return min(100.0, round(($this->processed_items / $total) * 100, 2));
    }

    /**
     * True cuando lo ya procesado supera el total_items estimado del batch
     * (estimación desactualizada, o el job procesó más de lo previsto).
     * La vista lo usa para avisar en vez de mostrar un % engañoso.
     */
    public function getExceedsEstimatedTotalAttribute(): bool
    {
        return $this->total_items > 0
            && ($this->processed_items + $this->failed_items) > $this->total_items;
    }

    /**
     * Calculate batch progress percentage
     */
    public function getBatchProgressPercentageAttribute(): float
    {
        if ($this->total_batches === 0) {
            return 0.0;
        }

        return round(($this->processed_batches / $this->total_batches) * 100, 2);
    }

    /**
     * Calculate success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        $processed = $this->processed_items + $this->failed_items;

        if ($processed === 0) {
            return 0.0;
        }

        return round(($this->processed_items / $processed) * 100, 2);
    }

    /**
     * Calculate remaining items
     */
    public function getRemainingItemsAttribute(): int
    {
        return $this->total_items - $this->processed_items - $this->failed_items;
    }

    /**
     * Check if batch is currently running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if batch is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if batch failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if batch was cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if batch is in a terminal (non-restartable) state
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled']);
    }

    /**
     * Check if batch can be cancelled (pending or running)
     */
    public function canCancel(): bool
    {
        return in_array($this->status, ['pending', 'running']);
    }

    /**
     * Check if batch can be retried
     */
    public function canRetry(): bool
    {
        return $this->retry_attempt < $this->max_retries &&
               in_array($this->status, ['failed', 'cancelled']);
    }

    /**
     * Mark batch as started
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark batch as completed
     */
    public function markAsCompleted(): void
    {
        $completedAt = now();
        $startedAt = $this->started_at;

        $this->update([
            'status' => 'completed',
            'completed_at' => $completedAt,
            'duration_seconds' => $startedAt ? $startedAt->diffInSeconds($completedAt) : null,
        ]);
    }

    /**
     * Mark batch as failed
     */
    public function markAsFailed(): void
    {
        $completedAt = now();
        $startedAt = $this->started_at;

        $this->update([
            'status' => 'failed',
            'completed_at' => $completedAt,
            'duration_seconds' => $startedAt ? $startedAt->diffInSeconds($completedAt) : null,
        ]);
    }

    /**
     * Mark batch as cancelled
     */
    public function markAsCancelled(): void
    {
        $completedAt = now();
        $startedAt = $this->started_at;

        $this->update([
            'status' => 'cancelled',
            'completed_at' => $completedAt,
            'duration_seconds' => $startedAt ? $startedAt->diffInSeconds($completedAt) : null,
        ]);
    }

    /**
     * Increment processed items
     */
    public function incrementProcessedItems(int $count = 1): void
    {
        $this->increment('processed_items', $count);
    }

    /**
     * Increment failed items
     */
    public function incrementFailedItems(int $count = 1): void
    {
        $this->increment('failed_items', $count);
    }

    /**
     * Increment processed batches
     */
    public function incrementProcessedBatches(int $count = 1): void
    {
        $this->increment('processed_batches', $count);
    }

    /**
     * Increment retry attempt
     */
    public function incrementRetryAttempt(): void
    {
        $this->update([
            'retry_attempt' => $this->retry_attempt + 1,
            'last_retry_at' => now(),
        ]);
    }

    /**
     * Scope: Get pending batches
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Get running batches
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope: Get completed batches
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Get failed batches
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Get batches by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('sync_type', $type);
    }

    /**
     * Scope: Get batches by priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: Get high priority batches
     */
    public function scopeHighPriority($query)
    {
        return $query->where(fn ($q) => $q->where('priority', 'high')->orWhere('priority', 'urgent'));
    }

    /**
     * Scope: Get retryable batches
     */
    public function scopeRetryable($query)
    {
        return $query->whereColumn('retry_attempt', '<', 'max_retries')
            ->whereIn('status', ['failed', 'cancelled']);
    }

    /**
     * Scope: Get dead (stuck) batches.
     *
     * Catches both:
     * - running batches whose worker died mid-run
     * - pending batches never picked up by a worker (queue jammed / worker down)
     */
    public function scopeDead($query, int $minutes = 120)
    {
        return $query
            ->whereIn('status', ['running', 'pending'])
            ->where(function ($q) use ($minutes) {
                $q->whereNull('updated_at')
                    ->orWhere('updated_at', '<=', now()->subMinutes($minutes));
            });
    }

    /**
     * Scope: Get running batches that were updated recently
     */
    public function scopeActive($query, int $minutes = 120)
    {
        return $query->where('status', 'running')
            ->where('updated_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope: Get recent batches
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get human-readable sync type name
     */
    public function getSyncTypeNameAttribute(): string
    {
        return match ($this->sync_type) {
            'product' => 'Producto',
            'category' => 'Categoría',
            'price' => 'Precio',
            'provider' => 'Proveedor',
            'model' => 'Modelo',
            default => ucfirst($this->sync_type),
        };
    }

    /**
     * Get human-readable status name
     */
    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'running' => 'En Progreso',
            'completed' => 'Completado',
            'failed' => 'Fallido',
            'cancelled' => 'Cancelado',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get human-readable priority name
     */
    public function getPriorityNameAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'Baja',
            'normal' => 'Normal',
            'high' => 'Alta',
            'urgent' => 'Urgente',
            default => ucfirst($this->priority),
        };
    }
}
