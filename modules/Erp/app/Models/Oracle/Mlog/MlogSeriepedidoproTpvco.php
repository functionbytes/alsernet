<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SERIEPEDIDOPRO_TPVCO
 * Tabla de replicación/materialización de Oracle
 */
class MlogSeriepedidoproTpvco extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_seriepedidopro_tpvco';

    protected $primaryKey = 'idseriepedidopro';

    public $incrementing = false;

    public $timestamps = false;
}
