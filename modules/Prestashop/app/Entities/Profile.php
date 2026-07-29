<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_profile';

    protected $primaryKey = 'id_profile';

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
