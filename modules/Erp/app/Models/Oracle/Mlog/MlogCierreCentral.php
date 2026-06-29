<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CIERRE_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogCierreCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_cierre_central';

    protected $primaryKey = 'idcierre';

    public $incrementing = false;

    public $timestamps = false;
}
