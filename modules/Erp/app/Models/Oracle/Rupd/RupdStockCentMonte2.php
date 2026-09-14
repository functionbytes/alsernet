<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_STOCK_CENT_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class RupdStockCentMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_stock_cent_monte2';

    protected $primaryKey = 'idstock';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
