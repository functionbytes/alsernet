<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class SearchEngine extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_search_engine';

    protected $primaryKey = 'id_search_engine';

    public $timestamps = false;

    protected $fillable = [
        'server',
        'getvar',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
