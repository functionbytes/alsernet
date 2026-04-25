<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Ecommerce\Enums\DiscountType;

class Discount extends Model
{
    use HasFactory;

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
            DiscountType::FIXED => min($this->value, $orderTotal),
            DiscountType::PERCENTAGE => $orderTotal * ($this->value / 100),
            DiscountType::FREE_SHIPPING => 0,
            default => 0,
        };
    }
}
