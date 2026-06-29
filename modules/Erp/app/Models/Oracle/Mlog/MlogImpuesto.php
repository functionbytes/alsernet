<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_IMPUESTO
 * Tabla de replicación/materialización de Oracle
 */
class MlogImpuesto extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_impuesto';

    protected $primaryKey = 'idimpuesto';

    public $incrementing = false;

    public $timestamps = false;
}
