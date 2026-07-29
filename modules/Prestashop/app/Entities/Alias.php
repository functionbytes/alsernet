<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Alias extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_alias';

    protected $primaryKey = 'id_alias';

    public $timestamps = false;

    protected $fillable = [
        'alias',
        'search',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
