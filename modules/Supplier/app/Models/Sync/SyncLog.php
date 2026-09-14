<?php

namespace Modules\Supplier\Models\Sync;

use App\Traits\HasUid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Sync\SyncLogFactory;

/**
 * Detailed event logs for each sync action
 *
 * Records individual sync operations with comprehensive details about
 * what was synced, the result, and any errors that occurred.
 *
 * @property int $id
 * @property string $uid ULID unique identifier
 * @property int $batch_id Associated batch ID
 * @property int|null $status_id Associated sync status ID
 * @property string $entity_type Type of entity: 'product', 'category', 'price', 'provider'
 * @property int|null $entity_id ID of the synced entity
 * @property int|null $erp_id ID from ERP system
 * @property string $action Action performed: 'create', 'update', 'delete', 'skip'
 * @property string $result Result: 'success', 'failed', 'skipped', 'warning'
 * @property string|null $message Detailed log message
 * @property array|null $data_before State before sync
 * @property array|null $data_after State after sync
 * @property array|null $changes Specific field changes
 * @property string|null $error_code Error code if failed
 * @property string|null $error_message Error message if failed
 * @property int $retry_count Number of retry attempts
 * @property int|null $duration_ms Execution time in milliseconds
 * @property string|null $triggered_by Trigger source: 'sync_job', 'manual_action', 'webhook', 'scheduled'
 * @property array|null $metadata Additional context data
 * @property Carbon|null $synced_at When the sync occurred
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SyncLog extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_sync_logs';

    protected static function newFactory(): SyncLogFactory
    {
        return SyncLogFactory::new();
    }

    protected $fillable = [
        'uid',
        'batch_id',
        'status_id',
        'entity_type',
        'entity_id',
        'erp_id',
        'action',
        'result',
        'message',
        'data_before',
        'data_after',
        'changes',
        'error_code',
        'error_message',
        'retry_count',
        'duration_ms',
        'triggered_by',
        'metadata',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'erp_id' => 'integer',
            'retry_count' => 'integer',
            'duration_ms' => 'integer',
            'data_before' => 'array',
            'data_after' => 'array',
            'changes' => 'array',
            'metadata' => 'array',
            'synced_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the associated sync batch
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(SyncBatch::class, 'batch_id');
    }

    /**
     * Get the associated sync status
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(SyncStatus::class, 'status_id');
    }

    /**
     * Check if log entry indicates success
     */
    public function isSuccess(): bool
    {
        return $this->result === 'success';
    }

    /**
     * Check if log entry indicates failure
     */
    public function isFailed(): bool
    {
        return $this->result === 'failed';
    }

    /**
     * Check if log entry indicates warning
     */
    public function isWarning(): bool
    {
        return $this->result === 'warning';
    }

    /**
     * Check if log entry indicates item was skipped
     */
    public function isSkipped(): bool
    {
        return $this->result === 'skipped';
    }

    /**
     * Check if this was a create action
     */
    public function isCreate(): bool
    {
        return $this->action === 'create';
    }

    /**
     * Check if this was an update action
     */
    public function isUpdate(): bool
    {
        return $this->action === 'update';
    }

    /**
     * Check if this was a delete action
     */
    public function isDelete(): bool
    {
        return $this->action === 'delete';
    }

    /**
     * Get changes summary
     */
    public function getChangesSummaryAttribute(): array
    {
        if (! $this->changes) {
            return [];
        }

        return array_map(function ($change) {
            return is_array($change) ? $change : ['before' => null, 'after' => $change];
        }, $this->changes);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if (! $this->duration_ms) {
            return 'N/A';
        }

        if ($this->duration_ms < 1000) {
            return "{$this->duration_ms}ms";
        }

        $seconds = round($this->duration_ms / 1000, 2);

        return "{$seconds}s";
    }

    /**
     * Scope: Get successful logs
     */
    public function scopeSuccessful($query)
    {
        return $query->where('result', 'success');
    }

    /**
     * Scope: Get failed logs
     */
    public function scopeFailed($query)
    {
        return $query->where('result', 'failed');
    }

    /**
     * Scope: Get warning logs
     */
    public function scopeWarnings($query)
    {
        return $query->where('result', 'warning');
    }

    /**
     * Scope: Get skipped logs
     */
    public function scopeSkipped($query)
    {
        return $query->where('result', 'skipped');
    }

    /**
     * Scope: Get logs by action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Get logs by entity type
     */
    public function scopeByEntityType($query, string $type)
    {
        return $query->where('entity_type', $type);
    }

    /**
     * Scope: Get logs by entity ID
     */
    public function scopeByEntityId($query, int $entityId)
    {
        return $query->where('entity_id', $entityId);
    }

    /**
     * Scope: Get logs by ERP ID
     */
    public function scopeByErpId($query, int $erpId)
    {
        return $query->where('erp_id', $erpId);
    }

    /**
     * Scope: Get logs with errors
     */
    public function scopeWithErrors($query)
    {
        return $query->where('result', 'failed')
            ->orWhere(function ($q) {
                $q->where('result', 'warning')
                    ->whereNotNull('error_message');
            });
    }

    /**
     * Scope: Get slow logs (over threshold milliseconds)
     */
    public function scopeSlow($query, int $thresholdMs = 5000)
    {
        return $query->where('duration_ms', '>', $thresholdMs)->whereNotNull('duration_ms');
    }

    /**
     * Scope: Get logs with retry attempts
     */
    public function scopeWithRetries($query)
    {
        return $query->where('retry_count', '>', 0);
    }

    /**
     * Scope: Get recent logs
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('synced_at', '>=', now()->subHours($hours)->startOfHour());
    }

    /**
     * Get human-readable action name
     */
    public function getActionNameAttribute(): string
    {
        return match ($this->action) {
            'create' => 'Crear',
            'update' => 'Actualizar',
            'delete' => 'Eliminar',
            'skip' => 'Omitir',
            default => ucfirst($this->action),
        };
    }

    /**
     * Get human-readable result name
     */
    public function getResultNameAttribute(): string
    {
        return match ($this->result) {
            'success' => 'Exitoso',
            'failed' => 'Fallido',
            'skipped' => 'Omitido',
            'warning' => 'Advertencia',
            default => ucfirst($this->result),
        };
    }

    /**
     * Get human-readable entity type name
     */
    public function getEntityTypeNameAttribute(): string
    {
        return match ($this->entity_type) {
            'product' => 'Producto',
            'category' => 'Categoría',
            'price' => 'Precio',
            'provider' => 'Proveedor',
            default => ucfirst($this->entity_type),
        };
    }
}
