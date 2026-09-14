<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SERIEALBARANCLI_DDLE
 * Tabla de replicación/materialización de Oracle
 */
class MlogSeriealbarancliDdle extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_seriealbarancli_ddle';

    protected $primaryKey = 'idseriealbarancli';

    public $incrementing = false;

    public $timestamps = false;
}
