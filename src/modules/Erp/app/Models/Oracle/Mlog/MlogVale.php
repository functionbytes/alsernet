<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_VALE
 * Tabla de replicación/materialización de Oracle
 */
class MlogVale extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_vale';

    protected $primaryKey = 'idvale';

    public $incrementing = false;

    public $timestamps = false;
}
