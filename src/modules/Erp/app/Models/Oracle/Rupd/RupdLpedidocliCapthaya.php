<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPEDIDOCLI_CAPTHAYA
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpedidocliCapthaya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpedidocli_capthaya';

    protected $primaryKey = 'idlpedidocli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
