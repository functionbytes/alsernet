<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_EMPRESA
 * Tabla de replicación/materialización de Oracle
 */
class MlogEmpresa extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_empresa';

    protected $primaryKey = 'idempresa';

    public $incrementing = false;

    public $timestamps = false;
}
