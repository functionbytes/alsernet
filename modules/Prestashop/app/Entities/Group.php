<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_group';

    protected $primaryKey = 'id_group';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'reduction',
        'price_display_method',
        'date_add',
        'date_upd',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'reduction' => 'float',
        'price_display_method' => 'float',
    ];
}
