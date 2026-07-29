<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Meta extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_meta';

    protected $primaryKey = 'id_meta';

    public $timestamps = false;

    protected $fillable = [
        'page',
        'title',
        'description',
        'keywords',
        'url_rewrite',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
