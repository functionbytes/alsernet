<?php

namespace Modules\Prestashop\Entities\Shop;

use Illuminate\Database\Eloquent\Model;

class ShopGroup extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_shop_group';

    protected $primaryKey = 'id_shop_group';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'color',
        'share_customer',
        'share_stock',
        'share_order',
        'deleted',
    ];

    protected $casts = [
        'deleted' => 'boolean',
    ];
}
