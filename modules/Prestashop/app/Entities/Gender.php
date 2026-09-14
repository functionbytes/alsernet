<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Gender extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_gender';

    protected $primaryKey = 'id_gender';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'type',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
