<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_COBROCLI_TPVCOR
 * Tabla de replicación/materialización de Oracle
 */
class MlogCobrocliTpvcor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_cobrocli_tpvcor';

    protected $primaryKey = 'idcobrocli';

    public $incrementing = false;

    public $timestamps = false;
}
