<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TIPODESCUENTO
 * Tabla de replicación/materialización de Oracle
 */
class MlogTipodescuento extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tipodescuento';

    protected $primaryKey = 'idtipodescuento';

    public $incrementing = false;

    public $timestamps = false;
}
