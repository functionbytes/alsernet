<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_STOCK_CENT_TPVCOR
 * Tabla de replicación/materialización de Oracle
 */
class RupdStockCentTpvcor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_stock_cent_tpvcor';

    protected $primaryKey = 'idstock';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
