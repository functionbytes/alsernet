<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_PEDIDOPRO_TPVCOR
 * Tabla de replicación/materialización de Oracle
 */
class RupdPedidoproTpvcor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_pedidopro_tpvcor';

    protected $primaryKey = 'idpedidopro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
