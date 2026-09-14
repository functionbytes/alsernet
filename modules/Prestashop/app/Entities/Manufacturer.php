<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_manufacturer';

    protected $primaryKey = 'id_manufacturer';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'description',
        'short_description',
        'id_address',
        'date_add',
        'date_upd',
        'link_rewrite',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'active',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'active' => 'boolean',
        'id_address' => 'integer',
    ];
}
