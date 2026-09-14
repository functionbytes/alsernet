<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LOTE
 * Tabla de replicación/materialización de Oracle
 */
class MlogLote extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lote';

    protected $primaryKey = 'idlote';

    public $incrementing = false;

    public $timestamps = false;
}
