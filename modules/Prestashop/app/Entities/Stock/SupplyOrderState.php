<?php

namespace Modules\Prestashop\Entities\Stock;

use Illuminate\Database\Eloquent\Model;

class SupplyOrderState extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_supply_order_state';

    protected $primaryKey = 'id_supply_order_state';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'delivery_note',
        'editable',
        'receipt_state',
        'pending_receipt',
        'enclosed',
        'color',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
