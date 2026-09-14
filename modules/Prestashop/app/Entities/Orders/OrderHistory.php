<?php

namespace Modules\Prestashop\Entities\Orders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Employee;

class OrderHistory extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_order_history';

    protected $primaryKey = 'id_order_history';

    public $timestamps = false;

    protected $fillable = [
        'id_order',
        'id_order_state',
        'id_employee',
        'date_add',
        'date_upd',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'id_order' => 'integer',
        'id_order_state' => 'integer',
        'id_employee' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_order');
    }

    public function orderState(): BelongsTo
    {
        return $this->belongsTo(OrderState::class, 'id_order_state');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'id_employee');
    }
}
