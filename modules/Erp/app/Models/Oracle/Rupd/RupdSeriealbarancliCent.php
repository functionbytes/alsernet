<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_SERIEALBARANCLI_CENT
 * Tabla de replicación/materialización de Oracle
 */
class RupdSeriealbarancliCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_seriealbarancli_cent';

    protected $primaryKey = 'idseriealbarancli_central';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
