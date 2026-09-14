<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_FACTURACLI_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogFacturacliCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_facturacli_central';

    protected $primaryKey = 'idfacturacli';

    public $incrementing = false;

    public $timestamps = false;
}
