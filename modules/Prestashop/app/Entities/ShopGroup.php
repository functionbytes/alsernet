<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Prestashop\Entities\Shop\ShopGroup;

class ShopGroup extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_shop_group';

    protected $primaryKey = 'id_shop_group';

    public $timestamps = false;

    protected $fillable = [
        'id_shop_group',
        'name',
        'color',
        'active',
        'share_customer',
        'share_stock',
        'share_order',
        'deleted',
    ];

    protected $casts = [
        'active' => 'boolean',
        'deleted' => 'boolean',
        'id_shop_group' => 'integer',
    ];

    public function shopGroup(): BelongsTo
    {
        return $this->belongsTo(ShopGroup::class, 'id_shop_group');
    }

    public function shops(): HasMany
    {
        return $this->hasMany('App\Models\Prestashop\Shop\Shop', 'id_shop_group', 'id_shop_group');
    }
}
