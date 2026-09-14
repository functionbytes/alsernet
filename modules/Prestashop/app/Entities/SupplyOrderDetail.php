<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Stock\SupplyOrder;

class SupplyOrderDetail extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_supply_order_detail';

    protected $primaryKey = 'id_supply_order_detail';

    public $timestamps = false;

    protected $fillable = [
        'id_supply_order_detail',
        'id_supply_order',
        'id_product',
        'id_product_attribute',
        'reference',
        'supplier_reference',
        'name',
        'ean13',
        'isbn',
        'upc',
        'mpn',
        'id_currency',
        'exchange_rate',
        'unit_price_te',
        'quantity_expected',
        'quantity_received',
        'price_te',
        'discount_rate',
        'discount_value_te',
        'price_with_discount_te',
        'tax_rate',
        'tax_value',
        'price_ti',
        'tax_value_with_order_discount',
        'price_with_order_discount_te',
    ];

    protected $casts = [
        'id_supply_order_detail' => 'integer',
        'id_supply_order' => 'integer',
        'id_product' => 'integer',
        'id_product_attribute' => 'integer',
        'id_currency' => 'integer',
        'quantity_expected' => 'integer',
        'quantity_received' => 'integer',
        'exchange_rate' => 'float',
        'unit_price_te' => 'float',
        'price_te' => 'float',
        'discount_rate' => 'float',
        'price_with_discount_te' => 'float',
        'tax_rate' => 'float',
        'price_ti' => 'float',
        'price_with_order_discount_te' => 'float',
    ];

    public function supplyOrder(): BelongsTo
    {
        return $this->belongsTo(SupplyOrder::class, 'id_supply_order');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product');
    }

    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo(Combination::class, 'id_product_attribute');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'id_currency');
    }
}
