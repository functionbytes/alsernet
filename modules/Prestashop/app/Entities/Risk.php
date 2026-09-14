<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Risk extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_risk';

    protected $primaryKey = 'id_risk';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'color',
        'percent',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
