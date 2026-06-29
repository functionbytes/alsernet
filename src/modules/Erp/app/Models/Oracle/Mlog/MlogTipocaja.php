<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TIPOCAJA
 * Tabla de replicación/materialización de Oracle
 */
class MlogTipocaja extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tipocaja';

    protected $primaryKey = 'idtipocaja';

    public $incrementing = false;

    public $timestamps = false;
}
