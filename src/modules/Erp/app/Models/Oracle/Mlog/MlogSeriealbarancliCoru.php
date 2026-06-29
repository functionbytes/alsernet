<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SERIEALBARANCLI_CORU
 * Tabla de replicación/materialización de Oracle
 */
class MlogSeriealbarancliCoru extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_seriealbarancli_coru';

    protected $primaryKey = 'idseriealbarancli';

    public $incrementing = false;

    public $timestamps = false;
}
