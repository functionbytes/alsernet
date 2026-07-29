<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Shop\Shop;
use Modules\Prestashop\Entities\Shop\ShopGroup;

class Delivery extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_delivery';

    protected $primaryKey = 'id_delivery';

    public $timestamps = false;

    protected $fillable = [
        'id_shop',
        'id_shop_group',
        'id_carrier',
        'id_range_price',
        'id_range_weight',
        'id_zone',
        'price',
    ];

    protected $casts = [
        'id_shop' => 'integer',
        'id_shop_group' => 'integer',
        'id_carrier' => 'integer',
        'id_range_price' => 'integer',
        'id_range_weight' => 'integer',
        'id_zone' => 'integer',
        'price' => 'float',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'id_shop');
    }

    public function shopGroup(): BelongsTo
    {
        return $this->belongsTo(ShopGroup::class, 'id_shop_group');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'id_carrier');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'id_zone');
    }
}
