<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Stock\SupplyOrder;

class SupplyOrderHistory extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_supply_order_history';

    protected $primaryKey = 'id_supply_order_history';

    public $timestamps = false;

    protected $fillable = [
        'id_supply_order_history',
        'id_supply_order',
        'id_employee',
        'employee_firstname',
        'employee_lastname',
        'id_state',
        'date_add',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'id_supply_order_history' => 'integer',
        'id_supply_order' => 'integer',
        'id_employee' => 'integer',
        'id_state' => 'integer',
    ];

    public function supplyOrder(): BelongsTo
    {
        return $this->belongsTo(SupplyOrder::class, 'id_supply_order');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'id_employee');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'id_state');
    }
}
