<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_BANCO
 * Tabla de replicación/materialización de Oracle
 */
class MlogBanco extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_banco';

    protected $primaryKey = 'idbanco';

    public $incrementing = false;

    public $timestamps = false;
}
