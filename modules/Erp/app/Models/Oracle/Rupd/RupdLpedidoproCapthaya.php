<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPEDIDOPRO_CAPTHAYA
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpedidoproCapthaya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpedidopro_capthaya';

    protected $primaryKey = 'idlpedidopro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
