<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_supplier';

    protected $primaryKey = 'id_supplier';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'description',
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
    ];
}
