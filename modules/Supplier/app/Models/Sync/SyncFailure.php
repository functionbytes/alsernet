<?php

namespace Modules\Supplier\Models\Sync;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * Track failed sync attempts for retry/analysis
 *
 * Dead Letter Queue para sincronizaciones fallidas (Supplier → ERP).
 * Almacena registros que fallaron en la sincronización bidireccional
 * para poder reintentar manualmente o diagnosticar problemas.
 *
 * @property int $id
 * @property string $uid ULID único (26 caracteres)
 * @property int|null $batch_id Associated sync batch ID
 * @property string $sync_type Tipo de sincronización: 'price', 'provider', 'product'
 * @property int $supplier_id ID del registro en tabla Supplier (SupplierProductPrice, SupplierErpProvider, Product)
 * @property int|null $entity_id ID de la entidad sincronizada
 * @property int|null $erp_id ID del registro en Oracle (ArtiprovTarifapro, Proveedor, Articulo)
 * @property array $changed_data Campos que se intentaron sincronizar
 * @property array|null $context Context información adicional
 * @property string $error_message Mensaje de error del fallo
 * @property string|null $error_code Código de error
 * @property int $retry_count Número de reintentos realizados
 * @property int $max_retries Máximo de reintentos permitidos
 * @property string $failure_status Estado del fallo: 'pending', 'resolved', 'acknowledged', 'archived'
 * @property Carbon|null $last_retry_at Última vez que se intentó reintentar
 * @property Carbon|null $resolved_at Cuando fue resuelto
 * @property int|null $resolved_by_user_id Usuario que resolvió el fallo
 * @property string|null $resolution_notes Notas sobre la resolución
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
use Illuminate\Support\Carbon;
use Modules\Supplier\Database\Factories\Sync\SyncFailureFactory;
use Modules\Supplier\Models\Supplier\Supplier;

class SyncFailure extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_sync_failures';

    protected $fillable = [
        'uid',
        'batch_id',
        'sync_type',
        'supplier_id',
        'entity_id',
        'erp_id',
        'changed_data',
        'context',
        'error_message',
        'error_code',
        'retry_count',
        'max_retries',
        'failure_type',
        'failure_status',
        'last_retry_at',
        'resolved_at',
        'resolved_by_user_id',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'changed_data' => 'array',
            'context' => 'array',
            'last_retry_at' => 'datetime',
            'resolved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the associated sync batch
     */
    protected static function newFactory(): SyncFailureFactory
    {
        return SyncFailureFactory::new();
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SyncBatch::class, 'batch_id');
    }

    /**
     * Get the supplier associated with this failure
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Verificar si el registro puede ser reintentado
     *
     * Condiciones para retry:
     * - retry_count < max_retries
     * - last_retry_at hace más de 30 minutos
     */
    public function canRetry(): bool
    {
        return $this->retry_count < $this->max_retries &&
               (! $this->last_retry_at || $this->last_retry_at->lessThan(now()->subMinutes(30)));
    }

    /**
     * Check if failure is resolved
     */
    public function isResolved(): bool
    {
        return $this->resolved_at !== null || $this->failure_status === 'resolved';
    }

    /**
     * Check if failure is pending
     */
    public function isPending(): bool
    {
        return $this->failure_status === 'pending';
    }

    /**
     * Check if failure is acknowledged
     */
    public function isAcknowledged(): bool
    {
        return $this->failure_status === 'acknowledged';
    }

    /**
     * Mark failure as resolved
     */
    public function markAsResolved(?int $userId = null, ?string $notes = null): void
    {
        $this->update([
            'resolved_at' => now(),
            'resolved_by_user_id' => $userId,
            'resolution_notes' => $notes,
            'failure_status' => 'resolved',
        ]);
    }

    /**
     * Mark failure as acknowledged
     */
    public function markAsAcknowledged(): void
    {
        $this->update(['failure_status' => 'acknowledged']);
    }

    /**
     * Increment retry count and update timestamp
     */
    public function recordRetryAttempt(): void
    {
        $this->update([
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now(),
        ]);
    }

    /**
     * Obtener descripción legible del tipo de sincronización
     */
    public function getTypeNameAttribute(): string
    {
        return match ($this->sync_type) {
            'price' => 'Precio de Producto',
            'provider' => 'Proveedor',
            'product' => 'Producto',
            default => $this->sync_type,
        };
    }

    /**
     * Registros ordenados por intento fallido más reciente
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeLatestFailures($query)
    {
        return $query->orderByDesc('last_retry_at')->orderByDesc('created_at');
    }

    /**
     * Filtrar por tipo de sincronización
     *
     * @param  Builder  $query
     * @param  string  $type
     * @return Builder
     */
    public function scopeByType($query, $type)
    {
        return $query->where('sync_type', $type);
    }

    /**
     * Filtrar por número de reintentos
     *
     * @param  Builder  $query
     * @param  int|null  $maxRetries
     * @return Builder
     */
    public function scopeRetryable($query, $maxRetries = null)
    {
        if ($maxRetries === null) {
            return $query->whereRaw('retry_count < max_retries');
        }

        return $query->where('retry_count', '<', $maxRetries);
    }

    /**
     * Scope: Get pending failures
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopePending($query)
    {
        return $query->where('failure_status', 'pending');
    }

    /**
     * Scope: Get resolved failures
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeResolved($query)
    {
        return $query->where('failure_status', 'resolved');
    }

    /**
     * Scope: Get acknowledged failures
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeAcknowledged($query)
    {
        return $query->where('failure_status', 'acknowledged');
    }

    /**
     * Scope: Get archived failures
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeArchived($query)
    {
        return $query->where('failure_status', 'archived');
    }

    /**
     * Scope: Get unresolved failures (pending and acknowledged)
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeUnresolved($query)
    {
        return $query->whereIn('failure_status', ['pending', 'acknowledged']);
    }

    /**
     * Scope: Get failures by batch
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByBatch($query, int $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope: Get failures by entity
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByEntity($query, int $entityId)
    {
        return $query->where('entity_id', $entityId);
    }

    /**
     * Scope: Get failures with error code
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByErrorCode($query, string $errorCode)
    {
        return $query->where('error_code', $errorCode);
    }

    /**
     * Scope: Get failures by failure type
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByFailureType($query, string $type)
    {
        return $query->where('failure_type', $type);
    }

    /**
     * Get human-readable failure status name
     */
    public function getFailureStatusNameAttribute(): string
    {
        return match ($this->failure_status) {
            'pending' => 'Pendiente',
            'resolved' => 'Resuelto',
            'acknowledged' => 'Reconocido',
            'archived' => 'Archivado',
            default => ucfirst($this->failure_status),
        };
    }

    /**
     * Get human-readable failure type name
     */
    public function getFailureTypeNameAttribute(): string
    {
        return match ($this->failure_type) {
            'sin_proveedor'  => 'Sin proveedor',
            'sin_categoria'  => 'Sin categoría',
            'sin_articulos'  => 'Sin artículos',
            'error_api'      => 'Error API',
            'datos_invalidos' => 'Datos inválidos',
            'error_db'       => 'Error base de datos',
            default          => $this->failure_type ?? 'Desconocido',
        };
    }
}
