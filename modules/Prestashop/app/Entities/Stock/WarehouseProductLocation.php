<?php

namespace Modules\Prestashop\Entities\Stock;

use Illuminate\Database\Eloquent\Model;

class WarehouseProductLocation extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_warehouse_product_location';

    protected $primaryKey = 'id_warehouse_product_location';

    public $timestamps = false;

    protected $fillable = [
        'id_product',
        'id_product_attribute',
        'id_warehouse',
        'location',
    ];

    protected $casts = [
        'id_product' => 'integer',
        'id_product_attribute' => 'integer',
        'id_warehouse' => 'integer',
    ];
}
