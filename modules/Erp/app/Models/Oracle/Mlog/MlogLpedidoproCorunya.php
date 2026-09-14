<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPEDIDOPRO_CORUNYA
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpedidoproCorunya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpedidopro_corunya';

    protected $primaryKey = 'idlpedidopro';

    public $incrementing = false;

    public $timestamps = false;
}
