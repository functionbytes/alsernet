<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class OrderCartRule extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_order_cart_rule';

    protected $primaryKey = 'id_order_cart_rule';

    public $timestamps = false;

    protected $fillable = [
        'id_order_cart_rule',
        'id_order',
        'id_cart_rule',
        'id_order_invoice',
        'name',
        'value',
        'value_tax_excl',
        'free_shipping',
        'deleted',
    ];

    protected $casts = [
        'deleted' => 'boolean',
        'id_order_cart_rule' => 'integer',
        'id_order' => 'integer',
        'id_cart_rule' => 'integer',
        'id_order_invoice' => 'integer',
    ];
}
