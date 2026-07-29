<?php

namespace Modules\Prestashop\Entities\Orders;

use Illuminate\Database\Eloquent\Model;

class OrderMessage extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_order_message';

    protected $primaryKey = 'id_order_message';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'message',
        'date_add',
    ];

    protected $casts = [
        'date_add' => 'datetime',
    ];
}
