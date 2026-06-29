<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPEDIDOCLI_CORUNYA
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpedidocliCorunya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpedidocli_corunya';

    protected $primaryKey = 'idlpedidocli';

    public $incrementing = false;

    public $timestamps = false;
}
