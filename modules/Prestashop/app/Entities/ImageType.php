<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class ImageType extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_image_type';

    protected $primaryKey = 'id_image_type';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'width',
        'height',
        'inventaries',
        'categories',
        'manufacturers',
        'suppliers',
        'stores',
    ];

    protected $casts = [
        'width' => 'float',
        'height' => 'float',
    ];
}
