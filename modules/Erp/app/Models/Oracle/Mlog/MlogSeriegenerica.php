<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SERIEGENERICA
 * Tabla de replicación/materialización de Oracle
 */
class MlogSeriegenerica extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_seriegenerica';

    protected $primaryKey = 'idseriegenerica';

    public $incrementing = false;

    public $timestamps = false;
}
