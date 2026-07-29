<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class RequestSql extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_request_sql';

    protected $primaryKey = 'id_request_sql';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'sql',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
