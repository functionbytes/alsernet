<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TIPOUNIDADES
 * Tabla de replicación/materialización de Oracle
 */
class MlogTipounidades extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tipounidades';

    protected $primaryKey = 'idtipounidades';

    public $incrementing = false;

    public $timestamps = false;
}
