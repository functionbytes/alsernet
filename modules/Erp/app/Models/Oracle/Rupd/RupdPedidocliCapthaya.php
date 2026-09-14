<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_PEDIDOCLI_CAPTHAYA
 * Tabla de replicación/materialización de Oracle
 */
class RupdPedidocliCapthaya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_pedidocli_capthaya';

    protected $primaryKey = 'idpedidocli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
