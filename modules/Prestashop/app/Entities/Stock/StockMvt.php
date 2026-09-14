<?php

namespace Modules\Prestashop\Entities\Stock;

use Illuminate\Database\Eloquent\Model;

class StockMvt extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_stock_mvt';

    protected $primaryKey = 'id_stock_mvt';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'date_add',
        'id_employee',
        'employee_firstname',
        'employee_lastname',
        'id_stock',
        'physical_quantity',
        'id_stock_mvt_reason',
        'sign',
        'price_te',
        'referer',
        'date_upd',
        'quantity',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'id_employee' => 'integer',
        'id_stock' => 'integer',
        'id_stock_mvt_reason' => 'integer',
        'quantity' => 'integer',
        'price_te' => 'float',
    ];
}
