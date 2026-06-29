<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPEDIDOPRO_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpedidoproMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpedidopro_monte2';

    protected $primaryKey = 'idlpedidopro';

    public $incrementing = false;

    public $timestamps = false;
}
