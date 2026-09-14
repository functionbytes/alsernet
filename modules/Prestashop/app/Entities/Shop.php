<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Prestashop\Entities\Shop\Shop;

class Shop extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_shop';

    protected $primaryKey = 'id_shop';

    public $timestamps = false;

    protected $fillable = [
        'id_shop',
        'id_shop_group',
        'id_category',
        'theme_name',
        'name',
        'color',
        'active',
        'deleted',
        'physical_uri',
        'virtual_uri',
        'domain',
        'domain_ssl',
        'theme',
    ];

    protected $casts = [
        'active' => 'boolean',
        'deleted' => 'boolean',
        'id_shop' => 'integer',
        'id_shop_group' => 'integer',
        'id_category' => 'integer',
    ];

    // BelongsTo Relationships
    public function shopGroup(): BelongsTo
    {
        return $this->belongsTo(
            'App\Models\Prestashop\ShopGroup',
            'id_shop_group',
            'id_shop_group'
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            'App\Models\Prestashop\Category',
            'id_category',
            'id_category'
        );
    }

    // HasMany Relationships
    public function customers(): HasMany
    {
        return $this->hasMany(
            'App\Models\Prestashop\Customer',
            'id_shop',
            'id_shop'
        );
    }

    public function orders(): HasMany
    {
        return $this->hasMany(
            'App\Models\Prestashop\Order',
            'id_shop',
            'id_shop'
        );
    }

    public function carts(): HasMany
    {
        return $this->hasMany(
            'App\Models\Prestashop\Cart',
            'id_shop',
            'id_shop'
        );
    }

    public function products(): HasMany
    {
        return $this->hasMany(
            'App\Models\Prestashop\Product',
            'id_shop_default',
            'id_shop'
        );
    }

    public function categories(): HasMany
    {
        return $this->hasMany(
            'App\Models\Prestashop\Category',
            'id_shop_default',
            'id_shop'
        );
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'id_shop');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Shop\ShopGroup', 'id_shop_group', 'id_shop_group');
    }

    public function carriers(): HasMany
    {
        return $this->hasMany('App\Models\Prestashop\Carrier', 'id_shop', 'id_shop');
    }
}
