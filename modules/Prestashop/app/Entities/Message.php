<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_message';

    protected $primaryKey = 'id_message';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'message',
        'id_cart',
        'id_order',
        'id_customer',
        'id_employee',
        'private',
        'date_add',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'id_cart' => 'integer',
        'id_order' => 'integer',
        'id_customer' => 'integer',
        'id_employee' => 'integer',
    ];
}
