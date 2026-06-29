<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LLOTEIDIOMA
 * Tabla de replicación/materialización de Oracle
 */
class MlogLloteidioma extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lloteidioma';

    protected $primaryKey = 'idlloteidioma';

    public $incrementing = false;

    public $timestamps = false;
}
