<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_EXPORTACION_CECA
 * Tabla de replicación/materialización de Oracle
 */
class MlogExportacionCeca extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_exportacion_ceca';

    protected $primaryKey = 'idexportacion_ceca';

    public $incrementing = false;

    public $timestamps = false;
}
