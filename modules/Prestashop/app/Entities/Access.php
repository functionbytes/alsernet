<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Access extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_access';

    protected $primaryKey = 'id_profile';

    public $timestamps = false;

    protected $fillable = [
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
