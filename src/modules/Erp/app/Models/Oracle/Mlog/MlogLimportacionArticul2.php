<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LIMPORTACION_ARTICUL2
 * Tabla de replicación/materialización de Oracle
 */
class MlogLimportacionArticul2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_limportacion_articul2';

    protected $primaryKey = 'idlimportacion_articulo_ext';

    public $incrementing = false;

    public $timestamps = false;
}
