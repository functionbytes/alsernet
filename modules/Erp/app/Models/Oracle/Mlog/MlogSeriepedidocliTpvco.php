<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SERIEPEDIDOCLI_TPVCO
 * Tabla de replicación/materialización de Oracle
 */
class MlogSeriepedidocliTpvco extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_seriepedidocli_tpvco';

    protected $primaryKey = 'idseriepedidocli';

    public $incrementing = false;

    public $timestamps = false;
}
