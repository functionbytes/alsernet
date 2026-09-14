<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customization extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_customization';

    protected $primaryKey = 'id_customization';

    public $timestamps = false;

    protected $fillable = [
        'id_product_attribute',
        'id_address_delivery',
        'id_cart',
        'id_product',
        'quantity',
        'quantity_refunded',
        'quantity_returned',
        'in_cart',
    ];

    protected $casts = [
        'id_product_attribute' => 'integer',
        'id_address_delivery' => 'integer',
        'id_cart' => 'integer',
        'id_product' => 'integer',
        'quantity' => 'integer',
        'quantity_refunded' => 'integer',
        'quantity_returned' => 'integer',
    ];

    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo(Combination::class, 'id_product_attribute');
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'id_address_delivery');
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'id_cart');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product');
    }
}
