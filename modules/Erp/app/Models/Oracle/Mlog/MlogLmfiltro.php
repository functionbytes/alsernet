<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LMFILTRO
 * Tabla de replicación/materialización de Oracle
 */
class MlogLmfiltro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lmfiltro';

    protected $primaryKey = 'idlmfiltro';

    public $incrementing = false;

    public $timestamps = false;
}
