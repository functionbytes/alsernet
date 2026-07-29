<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductDownload extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_product_download';

    protected $primaryKey = 'id_product_download';

    public $timestamps = false;

    protected $fillable = [
        'id_product',
        'display_filename',
        'filename',
        'date_add',
        'date_expiration',
        'nb_days_accessible',
        'nb_downloadable',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'id_product' => 'integer',
    ];
}
