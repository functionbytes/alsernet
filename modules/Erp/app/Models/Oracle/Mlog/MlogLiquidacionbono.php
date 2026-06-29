<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LIQUIDACIONBONO
 * Tabla de replicación/materialización de Oracle
 */
class MlogLiquidacionbono extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_liquidacionbono';

    protected $primaryKey = 'idliquidacionbono';

    public $incrementing = false;

    public $timestamps = false;
}
