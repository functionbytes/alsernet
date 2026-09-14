<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_POBLACION
 * Tabla de replicación/materialización de Oracle
 */
class MlogPoblacion extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_poblacion';

    protected $primaryKey = 'idpoblacion';

    public $incrementing = false;

    public $timestamps = false;
}
