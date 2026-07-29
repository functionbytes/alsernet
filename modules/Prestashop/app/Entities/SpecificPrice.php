<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class SpecificPrice extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_specific_price';

    protected $primaryKey = 'id_specific_price';

    public $timestamps = false;

    protected $fillable = [
        'id_product',
        'id_product_attribute',
        'id_shop',
        'id_shop_group',
        'id_currency',
        'id_country',
        'id_group',
        'id_customer',
        'price',
        'from_quantity',
        'reduction',
        'reduction_type',
        'from',
        'to',
    ];

    protected $casts = [
        'id_product' => 'integer',
        'id_product_attribute' => 'integer',
        'id_shop' => 'integer',
        'id_shop_group' => 'integer',
        'id_currency' => 'integer',
        'id_country' => 'integer',
        'id_group' => 'integer',
        'id_customer' => 'integer',
        'price' => 'float',
        'reduction' => 'float',
        'reduction_type' => 'float',
    ];
}
