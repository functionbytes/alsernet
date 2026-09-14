<?php

namespace Modules\Prestashop\Entities\Stock;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Combination;
use Modules\Prestashop\Entities\Currency;
use Modules\Prestashop\Entities\Product;

class SupplyOrderDetail extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_supply_order_detail';

    protected $primaryKey = 'id_supply_order_detail';

    public $timestamps = false;

    protected $fillable = [
        'id_supply_order',
        'id_product',
        'id_product_attribute',
        'reference',
        'supplier_reference',
        'name',
        'isbn',
        'upc',
        'mpn',
        'id_currency',
        'exchange_rate',
    ];

    protected $casts = [
        'id_supply_order' => 'integer',
        'id_product' => 'integer',
        'id_product_attribute' => 'integer',
        'id_currency' => 'integer',
        'exchange_rate' => 'float',
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
