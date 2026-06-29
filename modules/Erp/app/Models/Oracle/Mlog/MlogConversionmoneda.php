<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CONVERSIONMONEDA
 * Tabla de replicación/materialización de Oracle
 */
class MlogConversionmoneda extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_conversionmoneda';

    protected $primaryKey = 'idconversionmoneda';

    public $incrementing = false;

    public $timestamps = false;
}
