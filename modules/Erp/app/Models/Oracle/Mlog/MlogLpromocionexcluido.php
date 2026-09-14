<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPROMOCIONEXCLUIDO
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpromocionexcluido extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpromocionexcluido';

    protected $primaryKey = 'idlpromocionexcluido';

    public $incrementing = false;

    public $timestamps = false;
}
