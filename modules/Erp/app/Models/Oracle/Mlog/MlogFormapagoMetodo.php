<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_FORMAPAGO_METODO
 * Tabla de replicación/materialización de Oracle
 */
class MlogFormapagoMetodo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_formapago_metodo';

    protected $primaryKey = 'idformapago_metodo';

    public $incrementing = false;

    public $timestamps = false;
}
