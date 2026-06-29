<?php

namespace Modules\Erp\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceValidation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id_product',
        'id_product_attribute',
        'id_country',
        'reference',
        'product_name',
        'old_price',
        'new_price',
        'adjusted_price',
        'difference',
        'difference_percentage',
        'status',
        'validation_type',
        'priority',
        'stock',
        'is_available',
        'is_second_hand',
        'validated_by',
        'validated_at',
        'applied_by',
        'applied_at',
        'notes',
        'batch_id',
        'processed_at',
    ];

    protected $casts = [
        'id_product' => 'integer',
        'id_product_attribute' => 'integer',
        'id_country' => 'integer',
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'adjusted_price' => 'decimal:2',
        'difference' => 'decimal:2',
        'difference_percentage' => 'decimal:2',
        'stock' => 'integer',
        'is_available' => 'boolean',
        'is_second_hand' => 'boolean',
        'validated_at' => 'datetime',
        'applied_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Relación con el historial
     */
    public function history(): HasMany
    {
        return $this->hasMany(PriceValidationHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Agregar entrada al historial
     */
    public function addHistory(
        string $action,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?float $oldPrice = null,
        ?float $newPrice = null,
        ?float $adjustedPrice = null,
        ?string $changedBy = null,
        ?string $notes = null,
        ?array $metadata = null
    ): PriceValidationHistory {
        return $this->history()->create([
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'adjusted_price' => $adjustedPrice,
            'action' => $action,
            'changed_by' => $changedBy,
            'ip_address' => request()->ip(),
            'notes' => $notes,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Cambiar estado y registrar en historial
     */
    public function changeStatus(string $newStatus, ?string $changedBy = null, ?string $notes = null): bool
    {
        $oldStatus = $this->status;

        $this->addHistory(
            action: 'status_changed',
            oldStatus: $oldStatus,
            newStatus: $newStatus,
            changedBy: $changedBy,
            notes: $notes
        );

        $this->status = $newStatus;

        return $this->save();
    }

    /**
     * Ajustar precio manualmente
     */
    public function adjustPrice(float $newPrice, ?string $changedBy = null, ?string $notes = null): bool
    {
        $oldPrice = $this->adjusted_price ?? $this->new_price;
        $oldStatus = $this->status;

        $this->addHistory(
            action: 'price_adjusted',
            oldStatus: $oldStatus,
            newStatus: 'adjusted',
            oldPrice: $oldPrice,
            adjustedPrice: $newPrice,
            changedBy: $changedBy,
            notes: $notes
        );

        $this->adjusted_price = $newPrice;
        $this->status = 'adjusted';

        // Recalcular diferencia
        $this->difference = $newPrice - $this->old_price;
        if ($this->old_price > 0) {
            $this->difference_percentage = (($newPrice - $this->old_price) / $this->old_price) * 100;
        }

        return $this->save();
    }

    /**
     * Aprobar validación
     */
    public function approve(?string $approvedBy = null, ?string $notes = null): bool
    {
        $this->addHistory(
            action: 'approved',
            oldStatus: $this->status,
            newStatus: 'approved',
            changedBy: $approvedBy,
            notes: $notes
        );

        $this->status = 'approved';
        $this->validated_by = $approvedBy;
        $this->validated_at = now();

        return $this->save();
    }

    /**
     * Rechazar validación
     */
    public function reject(?string $rejectedBy = null, ?string $notes = null): bool
    {
        $this->addHistory(
            action: 'rejected',
            oldStatus: $this->status,
            newStatus: 'rejected',
            changedBy: $rejectedBy,
            notes: $notes
        );

        $this->status = 'rejected';
        $this->validated_by = $rejectedBy;
        $this->validated_at = now();

        return $this->save();
    }

    /**
     * Marcar como aplicado
     */
    public function markAsApplied(?string $appliedBy = null, ?string $notes = null): bool
    {
        $this->addHistory(
            action: 'applied',
            oldStatus: $this->status,
            newStatus: 'applied',
            changedBy: $appliedBy,
            notes: $notes
        );

        $this->status = 'applied';
        $this->applied_by = $appliedBy;
        $this->applied_at = now();

        return $this->save();
    }

    /**
     * Calcular prioridad basada en diferencia de precio
     */
    public static function calculatePriority(float $differencePercentage): string
    {
        $abs = abs($differencePercentage);

        if ($abs >= 50) {
            return 'critical';
        }
        if ($abs >= 20) {
            return 'high';
        }
        if ($abs >= 5) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Scopes para filtros comunes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByCountry($query, int $countryId)
    {
        return $query->where('id_country', $countryId);
    }

    public function scopeByProduct($query, int $productId)
    {
        return $query->where('id_product', $productId);
    }

    public function scopeByBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'critical']);
    }

    public function scopeWithStock($query)
    {
        return $query->where('stock', '>', 0);
    }
}
