<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TABLA
 * Tabla de replicación/materialización de Oracle
 */
class MlogTabla extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tabla';

    protected $primaryKey = 'idtabla';

    public $incrementing = false;

    public $timestamps = false;
}
