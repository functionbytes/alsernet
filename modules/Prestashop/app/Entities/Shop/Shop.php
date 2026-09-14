<?php

namespace Modules\Prestashop\Entities\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Category;

class Shop extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_shop';

    protected $primaryKey = 'id_shop';

    public $timestamps = false;

    protected $fillable = [
        'id_shop_group',
        'id_category',
        'theme_name',
        'name',
        'color',
        'deleted',
        'physical_uri',
        'virtual_uri',
        'domain',
        'domain_ssl',
        'theme',
    ];

    protected $casts = [
        'deleted' => 'boolean',
        'id_shop_group' => 'integer',
        'id_category' => 'integer',
    ];

    public function shopGroup(): BelongsTo
    {
        return $this->belongsTo(ShopGroup::class, 'id_shop_group');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'id_category');
    }
}
