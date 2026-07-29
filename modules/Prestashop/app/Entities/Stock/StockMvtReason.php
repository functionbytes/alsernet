<?php

namespace Modules\Prestashop\Entities\Stock;

use Illuminate\Database\Eloquent\Model;

class StockMvtReason extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_stock_mvt_reason';

    protected $primaryKey = 'id_stock_mvt_reason';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'sign',
        'date_add',
        'date_upd',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
