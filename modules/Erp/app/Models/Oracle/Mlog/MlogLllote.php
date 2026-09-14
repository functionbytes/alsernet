<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LLLOTE
 * Tabla de replicación/materialización de Oracle
 */
class MlogLllote extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lllote';

    protected $primaryKey = 'idlllote';

    public $incrementing = false;

    public $timestamps = false;
}
