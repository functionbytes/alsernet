<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_EXPORTACION_CECA_FPP
 * Tabla de replicación/materialización de Oracle
 */
class MlogExportacionCecaFpp extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_exportacion_ceca_fpp';

    protected $primaryKey = 'idexportacion_ceca_fppedcli';

    public $incrementing = false;

    public $timestamps = false;
}
