<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LLOTE
 * Tabla de replicación/materialización de Oracle
 */
class MlogLlote extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_llote';

    protected $primaryKey = 'idllote';

    public $incrementing = false;

    public $timestamps = false;
}
