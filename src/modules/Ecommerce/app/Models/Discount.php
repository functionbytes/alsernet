<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Ecommerce\Database\Factories\DiscountFactory;
use Modules\Ecommerce\Enums\DiscountType;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Discount extends Model
{
    use HasFactory;
    use LogsActivity;

    protected static function newFactory(): DiscountFactory
    {
        return DiscountFactory::new();
    }

    protected $table = 'ecommerce_discounts';

    protected $fillable = [
        'title',
        'code',
        'start_date',
        'end_date',
        'quantity',
        'total_used',
        'value',
        'type',
        'target',
        'min_order_price',
        'is_active',
        'description',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title',
                'code',
                'value',
                'type',
                'is_active',
                'quantity',
                'total_used',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('ecommerce_discount');
    }

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'is_active' => 'boolean',
            'value' => 'decimal:2',
            'min_order_price' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        if ($this->end_date && now()->greaterThan($this->end_date)) {
            return true;
        }

        if ($this->quantity !== null && $this->total_used >= $this->quantity) {
            return true;
        }

        return false;
    }

    public function calculateDiscount(float $orderTotal): float
    {
        if (! $this->is_active || $this->isExpired()) {
            return 0;
        }

        if ($this->min_order_price && $orderTotal < $this->min_order_price) {
            return 0;
        }

        return match ($this->type) {
            // El importe de un descuento es dinero: redondear a 2 decimales (el
            // porcentaje producía céntimos fraccionarios, p.ej. 99.99×33% =
            // 32.9967, que se propagaban a total/factura/pago) y topar al total
            // del pedido (un porcentaje >100% mal configurado descontaba más que
            // el total → total negativo; FIXED ya estaba topado con min()).
            DiscountType::FIXED => round(min((float) $this->value, $orderTotal), 2),
            DiscountType::PERCENTAGE => round(min($orderTotal, $orderTotal * ((float) $this->value / 100)), 2),
            DiscountType::FREE_SHIPPING => 0,
            default => 0,
        };
    }
}
