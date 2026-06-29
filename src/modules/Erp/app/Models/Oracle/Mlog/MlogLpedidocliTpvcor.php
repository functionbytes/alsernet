<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPEDIDOCLI_TPVCOR
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpedidocliTpvcor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpedidocli_tpvcor';

    protected $primaryKey = 'idlpedidocli';

    public $incrementing = false;

    public $timestamps = false;
}
