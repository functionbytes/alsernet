<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PAIS
 * Tabla de replicación/materialización de Oracle
 */
class MlogPais extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_pais';

    protected $primaryKey = 'idpais';

    public $incrementing = false;

    public $timestamps = false;
}
