<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_SERIEALBARANCLI_DDLE
 * Tabla de replicación/materialización de Oracle
 */
class RupdSeriealbarancliDdle extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_seriealbarancli_ddle';

    protected $primaryKey = 'idseriealbarancli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
