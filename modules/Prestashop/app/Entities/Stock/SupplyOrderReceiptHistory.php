<?php

namespace Modules\Prestashop\Entities\Stock;

use Illuminate\Database\Eloquent\Model;

class SupplyOrderReceiptHistory extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_supply_order_receipt_history';

    protected $primaryKey = 'id_supply_order_receipt_history';

    public $timestamps = false;

    protected $fillable = [
        'id_supply_order_detail',
        'id_employee',
        'employee_firstname',
        'employee_lastname',
        'id_supply_order_state',
        'quantity',
        'date_add',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'id_supply_order_detail' => 'integer',
        'id_employee' => 'integer',
        'id_supply_order_state' => 'integer',
        'quantity' => 'integer',
    ];
}
