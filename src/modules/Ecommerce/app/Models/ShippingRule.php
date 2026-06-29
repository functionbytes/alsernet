<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Ecommerce\Database\Factories\ShippingRuleFactory;

class ShippingRule extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_shipping_rules';

    protected static function newFactory(): ShippingRuleFactory
    {
        return ShippingRuleFactory::new();
    }

    protected $fillable = [
        'name',
        'shipping_id',
        'type',
        'currency_id',
        'from',
        'to',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'from' => 'decimal:2',
            'to' => 'decimal:2',
            'price' => 'decimal:2',
        ];
    }

    public function shipping(): BelongsTo
    {
        return $this->belongsTo(Shipping::class, 'shipping_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShippingRuleItem::class, 'shipping_rule_id');
    }
}
