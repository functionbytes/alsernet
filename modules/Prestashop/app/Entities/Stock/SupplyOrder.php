<?php

namespace Modules\Prestashop\Entities\Stock;

use Illuminate\Database\Eloquent\Model;

class SupplyOrder extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_supply_order';

    protected $primaryKey = 'id_supply_order';

    public $timestamps = false;

    protected $fillable = [
        'id_supplier',
        'id_lang',
        'id_warehouse',
        'id_supply_order_state',
        'id_currency',
        'id_ref_currency',
        'reference',
        'date_add',
        'date_upd',
        'date_delivery_expected',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'id_supplier' => 'integer',
        'id_lang' => 'integer',
        'id_warehouse' => 'integer',
        'id_supply_order_state' => 'integer',
        'id_currency' => 'integer',
        'id_ref_currency' => 'integer',
    ];
}
