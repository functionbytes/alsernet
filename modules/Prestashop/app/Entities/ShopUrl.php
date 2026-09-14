<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Shop\Shop;

class ShopUrl extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_shop_url';

    protected $primaryKey = 'id_shop_url';

    public $timestamps = false;

    protected $fillable = [
        'id_shop_url',
        'id_shop',
        'domain',
        'domain_ssl',
        'physical_uri',
        'virtual_uri',
        'main',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'id_shop_url' => 'integer',
        'id_shop' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'id_shop');
    }
}
