<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_EXPORTACION_CECA_FPP
 * Tabla de replicación/materialización de Oracle
 */
class RupdExportacionCecaFpp extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_exportacion_ceca_fpp';

    protected $primaryKey = 'idexportacion_ceca_fppedcli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
