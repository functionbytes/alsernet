<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SERIEALBARANCLI_CENT
 * Tabla de replicación/materialización de Oracle
 */
class MlogSeriealbarancliCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_seriealbarancli_cent';

    protected $primaryKey = 'idseriealbarancli_central';

    public $incrementing = false;

    public $timestamps = false;
}
