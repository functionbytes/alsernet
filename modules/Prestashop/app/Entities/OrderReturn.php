<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Orders\Order;

class OrderReturn extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_order_return';

    protected $primaryKey = 'id_order_return';

    public $timestamps = false;

    protected $fillable = [
        'id_order_return',
        'id_customer',
        'id_order',
        'state',
        'question',
        'date_add',
        'date_upd',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'id_order_return' => 'integer',
        'id_customer' => 'integer',
        'id_order' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_order');
    }
}
