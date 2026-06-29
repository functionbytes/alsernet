<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRuleItem extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_shipping_rule_items';

    protected $fillable = [
        'shipping_rule_id',
        'country',
        'state',
        'city',
        'adjustment_price',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'adjustment_price' => 'decimal:2',
            'is_enabled' => 'boolean',
        ];
    }

    public function shippingRule(): BelongsTo
    {
        return $this->belongsTo(ShippingRule::class, 'shipping_rule_id');
    }
}
