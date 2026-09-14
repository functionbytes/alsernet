<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class CustomerSession extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_customer_session';

    protected $primaryKey = 'id_customer_session';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_customer',
        'token',
    ];

    protected $casts = [
        'id_customer' => 'integer',
    ];
}
