<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPEDIDOCLI_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpedidocliCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpedidocli_central';

    protected $primaryKey = 'idlpedidocli_central';

    public $incrementing = false;

    public $timestamps = false;
}
