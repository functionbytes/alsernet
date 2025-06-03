<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturnCost extends Model
{
    use HasFactory;

    protected $table = 'return_costs';

    protected $fillable = [
        'return_id',
        'cost_type',
        'amount',
        'description',
        'is_automatic',
        'applied_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_automatic' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Constantes para tipos de coste
    const TYPE_SHIPPING = 'shipping';
    const TYPE_RESTOCKING = 'restocking';
    const TYPE_INSPECTION = 'inspection';
    const TYPE_DAMAGE = 'damage';
    const TYPE_OTHER = 'other';

    // Estados de costes
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // Relaciones
    public function return()
    {
        return $this->belongsTo('App\Models\Return\ReturnRequest');
    }

    // Scopes
    public function scopeAutomatic($query)
    {
        return $query->where('is_automatic', true);
    }

    public function scopeManual($query)
    {
        return $query->where('is_automatic', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('cost_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Métodos de acceso
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2) . ' €';
    }

    public function getCostTypeLabelAttribute()
    {
        $labels = [
            self::TYPE_SHIPPING => 'Gastos de Envío',
            self::TYPE_RESTOCKING => 'Reposición de Stock',
            self::TYPE_INSPECTION => 'Inspección',
            self::TYPE_DAMAGE => 'Daños',
            self::TYPE_OTHER => 'Otros'
        ];

        return $labels[$this->cost_type] ?? 'Desconocido';
    }
}
