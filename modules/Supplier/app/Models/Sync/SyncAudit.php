<?php

namespace Modules\Supplier\Models\Sync;

use App\Traits\HasUid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Supplier\Database\Factories\Sync\SyncAuditFactory;

/**
 * Audit Trail para sincronizaciones ERP ↔ Supplier
 *
 * Registra cada ciclo de sincronización con métricas de performance,
 * estadísticas de registros, y metadata adicional para troubleshooting.
 *
 * @property int $id
 * @property string $uid
 * @property string $entity_type (sports, categories, providers, products, etc.)
 * @property string $sync_direction (erp_to_supplier, supplier_to_erp)
 * @property int $records_synced
 * @property int $records_failed
 * @property int $records_skipped
 * @property float $elapsed_seconds
 * @property float|null $memory_mb
 * @property float|null $peak_memory_mb
 * @property array|null $metadata
 * @property int|null $cycle_number
 * @property string|null $triggered_by
 * @property Carbon $synced_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SyncAudit extends Model
{
    use HasFactory, HasUid;

    protected static function newFactory(): SyncAuditFactory
    {
        return SyncAuditFactory::new();
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'supplier_sync_audit';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uid',
        'entity_type',
        'sync_direction',
        'records_synced',
        'records_failed',
        'records_skipped',
        'elapsed_seconds',
        'memory_mb',
        'peak_memory_mb',
        'metadata',
        'cycle_number',
        'triggered_by',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'records_synced' => 'integer',
        'records_failed' => 'integer',
        'records_skipped' => 'integer',
        'elapsed_seconds' => 'float',
        'memory_mb' => 'float',
        'peak_memory_mb' => 'float',
        'metadata' => 'array',
        'cycle_number' => 'integer',
        'synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope: Get audits by entity type
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope: Get audits by sync direction
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByDirection($query, string $direction)
    {
        return $query->where('sync_direction', $direction);
    }

    /**
     * Scope: Get recent audits (last 24 hours by default)
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('synced_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope: Get audits with failures
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeWithFailures($query)
    {
        return $query->where('records_failed', '>', 0);
    }

    /**
     * Scope: Get slow syncs (over threshold in seconds)
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeSlow($query, float $thresholdSeconds = 10.0)
    {
        return $query->where('elapsed_seconds', '>', $thresholdSeconds);
    }

    /**
     * Get total records processed
     */
    public function getTotalProcessedAttribute(): int
    {
        return $this->records_synced + $this->records_failed + $this->records_skipped;
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        $total = $this->getTotalProcessedAttribute();

        if ($total === 0) {
            return 100.0;
        }

        return round(($this->records_synced / $total) * 100, 2);
    }

    /**
     * Check if sync had failures
     */
    public function hasFailures(): bool
    {
        return $this->records_failed > 0;
    }

    /**
     * Check if sync was slow (over 10 seconds by default)
     */
    public function isSlow(float $threshold = 10.0): bool
    {
        return $this->elapsed_seconds > $threshold;
    }

    /**
     * Get human-readable entity type
     */
    public function getEntityTypeNameAttribute(): string
    {
        return match ($this->entity_type) {
            'sports' => 'Deportes',
            'categories' => 'Categorías',
            'families' => 'Familias',
            'subfamilies' => 'Subfamilias',
            'groups' => 'Grupos',
            'providers' => 'Proveedores',
            'products' => 'Productos',
            'provider_products' => 'Productos-Proveedor',
            'prices' => 'Precios',
            default => ucfirst($this->entity_type),
        };
    }

    /**
     * Get human-readable sync direction
     */
    public function getSyncDirectionNameAttribute(): string
    {
        return match ($this->sync_direction) {
            'erp_to_supplier' => 'ERP → Supplier',
            'supplier_to_erp' => 'Supplier → ERP',
            default => $this->sync_direction,
        };
    }
}
