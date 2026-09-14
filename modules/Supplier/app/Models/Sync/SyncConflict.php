<?php

namespace Modules\Supplier\Models\Sync;

use App\Models\User;
use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Sync\SyncConflictFactory;

class SyncConflict extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_sync_conflicts';

    protected static function newFactory(): SyncConflictFactory
    {
        return SyncConflictFactory::new();
    }

    protected $fillable = [
        'entity_type',
        'entity_id',
        'erp_id',
        'resolution_strategy',
        'local_data',
        'erp_data',
        'resolved_data',
        'changed_fields',
        'conflict_detected_at',
        'resolved_at',
        'resolved_by_user_id',
        'resolved_by_ip',
        'notes',
    ];

    protected $casts = [
        'local_data' => 'array',
        'erp_data' => 'array',
        'resolved_data' => 'array',
        'changed_fields' => 'array',
        'conflict_detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Resolution strategies
     */
    const STRATEGY_ERP_WINS = 'erp_wins';

    const STRATEGY_LOCAL_WINS = 'local_wins';

    const STRATEGY_MANUAL = 'manual';

    const STRATEGY_MERGE = 'merge';

    /**
     * Entity types
     */
    const TYPE_PRICE = 'price';

    const TYPE_PRODUCT = 'product';

    const TYPE_PROVIDER = 'provider';

    /**
     * User who resolved the conflict
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    /**
     * Scopes
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    public function scopeByEntityType($query, string $type)
    {
        return $query->where('entity_type', $type);
    }

    public function scopeByStrategy($query, string $strategy)
    {
        return $query->where('resolution_strategy', $strategy);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('conflict_detected_at', '>=', now()->subDays($days));
    }

    /**
     * Mark conflict as resolved
     */
    public function markAsResolved(?int $userId = null, ?string $ip = null, ?string $notes = null): void
    {
        $this->update([
            'resolved_at' => now(),
            'resolved_by_user_id' => $userId,
            'resolved_by_ip' => $ip,
            'notes' => $notes,
        ]);
    }

    /**
     * Check if conflict is resolved
     */
    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * Get human-readable entity type
     */
    public function getEntityTypeNameAttribute(): string
    {
        return match ($this->entity_type) {
            self::TYPE_PRICE => 'Precio',
            self::TYPE_PRODUCT => 'Producto',
            self::TYPE_PROVIDER => 'Proveedor',
            default => 'Desconocido',
        };
    }

    /**
     * Get human-readable resolution strategy
     */
    public function getResolutionStrategyNameAttribute(): string
    {
        return match ($this->resolution_strategy) {
            self::STRATEGY_ERP_WINS => 'ERP Gana',
            self::STRATEGY_LOCAL_WINS => 'Local Gana',
            self::STRATEGY_MANUAL => 'Manual',
            self::STRATEGY_MERGE => 'Merge',
            default => 'Desconocido',
        };
    }
}
