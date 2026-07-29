<?php

namespace Modules\Prestashop\Entities\Orders;

use Illuminate\Database\Eloquent\Model;

class OrderReturnState extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_order_return_state';

    protected $primaryKey = 'id_order_return_state';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'color',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
