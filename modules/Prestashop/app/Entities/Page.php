<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_page';

    protected $primaryKey = 'id_page';

    public $timestamps = false;

    protected $fillable = [
        'id_page',
        'id_page_type',
        'id_object',
        'name',
    ];

    protected $casts = [
        'id_page' => 'integer',
        'id_page_type' => 'integer',
        'id_object' => 'integer',
    ];
}
