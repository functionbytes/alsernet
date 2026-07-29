<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Stock\Warehouse;

class WarehouseProductLocation extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_warehouse_product_location';

    protected $primaryKey = 'id_warehouse_product_location';

    public $timestamps = false;

    protected $fillable = [
        'id_warehouse_product_location',
        'id_product',
        'id_product_attribute',
        'id_warehouse',
        'location',
    ];

    protected $casts = [
        'id_warehouse_product_location' => 'integer',
        'id_product' => 'integer',
        'id_product_attribute' => 'integer',
        'id_warehouse' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product');
    }

    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo(Combination::class, 'id_product_attribute');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse');
    }
}
