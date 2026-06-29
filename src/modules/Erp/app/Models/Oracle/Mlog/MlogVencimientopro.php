<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_VENCIMIENTOPRO
 * Tabla de replicación/materialización de Oracle
 */
class MlogVencimientopro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_vencimientopro';

    protected $primaryKey = 'idvencimientopro';

    public $incrementing = false;

    public $timestamps = false;
}
