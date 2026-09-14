<?php

namespace Modules\Prestashop\Entities\Stock;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_warehouse';

    protected $primaryKey = 'id_warehouse';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_address',
        'reference',
        'name',
        'id_employee',
        'id_currency',
        'management_type',
    ];

    protected $casts = [
        'id_address' => 'integer',
        'id_employee' => 'integer',
        'id_currency' => 'integer',
    ];
}
