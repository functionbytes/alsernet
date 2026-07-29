<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_image';

    protected $primaryKey = 'id_image';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_product',
        'position',
        'cover',
        'legend',
        'source_index',
    ];

    protected $casts = [
        'id_product' => 'integer',
        'position' => 'integer',
    ];
}
