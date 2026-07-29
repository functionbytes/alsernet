<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class OrderReturnState extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_order_return_state';

    protected $primaryKey = 'id_order_return_state';

    public $timestamps = false;

    protected $fillable = [
        'id_order_return_state',
        'name',
        'color',
    ];

    protected $casts = [
        'id_order_return_state' => 'integer',
    ];
}
