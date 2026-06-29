<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_EXPORTACION_CECA
 * Tabla de replicación/materialización de Oracle
 */
class RupdExportacionCeca extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_exportacion_ceca';

    protected $primaryKey = 'idexportacion_ceca';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
