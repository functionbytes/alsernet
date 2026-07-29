<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_zone';

    protected $primaryKey = 'id_zone';

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
