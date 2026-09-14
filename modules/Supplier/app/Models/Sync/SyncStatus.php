<?php

namespace Modules\Supplier\Models\Sync;

use App\Traits\HasUid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/**
 * Track overall sync progress and status
 *
 * Maintains high-level tracking of synchronization operations between
 * the Supplier module and the ERP system, including progress metrics,
 * timing information, and status tracking.
 *
 * @property int $id
 * @property string $uid ULID unique identifier
 * @property int|null $supplier_id Supplier ID (for supplier-specific syncs)
 * @property int|null $batch_id Associated batch ID
 * @property string $sync_type Type of sync: 'product', 'category', 'price', 'provider'
 * @property string $sync_scope Scope: 'all', 'per_supplier', 'per_category'
 * @property string $status Status: 'pending', 'running', 'completed', 'failed', 'paused'
 * @property int $total_items Total items to be synced
 * @property int $synced_items Items successfully synced
 * @property int $failed_items Items that failed
 * @property int $skipped_items Items that were skipped
 * @property Carbon|null $started_at When the sync started
 * @property Carbon|null $completed_at When the sync completed
 * @property float|null $elapsed_seconds Total execution time
 * @property float|null $memory_used_mb Memory consumed
 * @property string|null $triggered_by Trigger source: 'manual', 'scheduled', 'webhook', 'api'
 * @property string|null $notes Additional notes
 * @property array|null $metadata Additional tracking data
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
use Modules\Supplier\Database\Factories\Sync\SyncStatusFactory;
use Modules\Supplier\Models\Supplier\Supplier;

class SyncStatus extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_sync_statuses';

    protected $fillable = [
        'uid',
        'supplier_id',
        'batch_id',
        'sync_type',
        'sync_scope',
        'status',
        'total_items',
        'synced_items',
        'failed_items',
        'skipped_items',
        'started_at',
        'completed_at',
        'elapsed_seconds',
        'memory_used_mb',
        'triggered_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'total_items' => 'integer',
            'synced_items' => 'integer',
            'failed_items' => 'integer',
            'skipped_items' => 'integer',
            'elapsed_seconds' => 'float',
            'memory_used_mb' => 'float',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the associated supplier
     */
    protected static function newFactory(): SyncStatusFactory
    {
        return SyncStatusFactory::new();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the associated sync batch
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(SyncBatch::class, 'batch_id');
    }

    /**
     * Get all logs related to this status
     */
    public function logs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'status_id');
    }

    /**
     * Get all failures related to this status
     */
    public function failures(): HasMany
    {
        return $this->hasMany(SyncFailure::class, 'batch_id', 'batch_id');
    }

    /**
     * Calculate progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_items === 0) {
            return 0.0;
        }

        return round(($this->synced_items / $this->total_items) * 100, 2);
    }

    /**
     * Calculate success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        $processed = $this->synced_items + $this->failed_items;

        if ($processed === 0) {
            return 0.0;
        }

        return round(($this->synced_items / $processed) * 100, 2);
    }

    /**
     * Check if sync is currently running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if sync is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if sync failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if sync can be retried
     */
    public function canRetry(): bool
    {
        return in_array($this->status, ['failed', 'paused']);
    }

    /**
     * Mark sync as started
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark sync as completed
     */
    public function markAsCompleted(): void
    {
        $completedAt = now();
        $startedAt = $this->started_at;

        $this->update([
            'status' => 'completed',
            'completed_at' => $completedAt,
            'elapsed_seconds' => $startedAt ? $completedAt->diffInSeconds($startedAt) : null,
        ]);
    }

    /**
     * Mark sync as failed with optional message
     */
    public function markAsFailed(?string $message = null): void
    {
        $completedAt = now();
        $startedAt = $this->started_at;

        $this->update([
            'status' => 'failed',
            'completed_at' => $completedAt,
            'elapsed_seconds' => $startedAt ? $completedAt->diffInSeconds($startedAt) : null,
            'notes' => $message ?? $this->notes,
        ]);
    }

    /**
     * Scope: Get running syncs
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope: Get completed syncs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Get failed syncs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Get syncs by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('sync_type', $type);
    }

    /**
     * Scope: Get syncs by scope
     */
    public function scopeByScope($query, string $scope)
    {
        return $query->where('sync_scope', $scope);
    }

    /**
     * Scope: Get syncs triggered by specific source
     */
    public function scopeTriggeredBy($query, string $trigger)
    {
        return $query->where('triggered_by', $trigger);
    }

    /**
     * Scope: Get recent syncs (last N hours)
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope: Get slow syncs (over threshold seconds)
     */
    public function scopeSlow($query, float $thresholdSeconds = 30.0)
    {
        return $query->where('elapsed_seconds', '>', $thresholdSeconds)->whereNotNull('elapsed_seconds');
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
            'paused' => 'Pausado',
            default => ucfirst($this->status),
        };
    }
}
