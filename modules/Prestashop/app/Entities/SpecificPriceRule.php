<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Shop\Shop;

class SpecificPriceRule extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_specific_price_rule';

    protected $primaryKey = 'id_specific_price_rule';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'id_shop',
        'id_currency',
        'id_country',
        'id_group',
        'from_quantity',
        'price',
        'reduction',
        'reduction_tax',
        'reduction_type',
        'from',
        'to',
    ];

    protected $casts = [
        'id_shop' => 'integer',
        'id_currency' => 'integer',
        'id_country' => 'integer',
        'id_group' => 'integer',
        'price' => 'float',
        'reduction' => 'float',
        'reduction_tax' => 'float',
        'reduction_type' => 'float',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'id_shop');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'id_currency');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'id_country');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'id_group');
    }
}
