<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartRule extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_cart_rule';

    protected $primaryKey = 'id_cart_rule';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'id_customer',
        'date_from',
        'date_to',
        'description',
        'code',
        'minimum_amount',
        'minimum_amount_tax',
        'minimum_amount_currency',
        'minimum_amount_shipping',
        'country_restriction',
        'carrier_restriction',
        'group_restriction',
        'cart_rule_restriction',
        'product_restriction',
        'shop_restriction',
        'free_shipping',
        'reduction_percent',
        'reduction_amount',
        'reduction_tax',
        'reduction_currency',
        'reduction_product',
        'reduction_exclude_special',
        'gift_product',
        'gift_product_attribute',
        'highlight',
        'date_add',
        'date_upd',
    ];

    protected $casts = [
        'date_from' => 'datetime',
        'date_to' => 'datetime',
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'gift_product' => 'boolean',
        'gift_product_attribute' => 'boolean',
        'id_customer' => 'integer',
        'reduction_percent' => 'float',
        'reduction_amount' => 'float',
        'reduction_tax' => 'float',
        'reduction_currency' => 'float',
        'reduction_product' => 'float',
        'reduction_exclude_special' => 'float',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }
}
