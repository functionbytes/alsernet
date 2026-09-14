<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_MONEDA
 * Tabla de replicación/materialización de Oracle
 */
class MlogMoneda extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_moneda';

    protected $primaryKey = 'idmoneda';

    public $incrementing = false;

    public $timestamps = false;
}
