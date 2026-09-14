<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPEDIDOCLI_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpedidocliMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpedidocli_monte2';

    protected $primaryKey = 'idlpedidocli';

    public $incrementing = false;

    public $timestamps = false;
}
