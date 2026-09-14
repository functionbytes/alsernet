<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Hook extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_hook';

    protected $primaryKey = 'id_hook';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'title',
        'description',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
