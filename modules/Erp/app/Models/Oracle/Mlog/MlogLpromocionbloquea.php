<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPROMOCIONBLOQUEA
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpromocionbloquea extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpromocionbloquea';

    protected $primaryKey = 'idlpromocionbloquea';

    public $incrementing = false;

    public $timestamps = false;
}
