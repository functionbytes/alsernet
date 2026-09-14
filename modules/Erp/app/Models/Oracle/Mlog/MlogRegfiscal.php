<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_REGFISCAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogRegfiscal extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_regfiscal';

    protected $primaryKey = 'idregfiscal';

    public $incrementing = false;

    public $timestamps = false;
}
