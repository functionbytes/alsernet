<?php

namespace Modules\HelpdeskPrestashop\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistedCartItem extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_assisted_cart_items';

    protected $fillable = [
        'assisted_cart_id',
        'product_id',
        'product_name',
        'sku',
        'image_url',
        'unit_price',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(AssistedCart::class, 'assisted_cart_id');
    }

    protected function lineTotal(): Attribute
    {
        return Attribute::make(
            get: fn () => round((float) $this->unit_price * (int) $this->quantity, 2),
        );
    }
}
