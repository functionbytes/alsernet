<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_contact';

    protected $primaryKey = 'id_contact';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'email',
        'description',
        'customer_service',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
