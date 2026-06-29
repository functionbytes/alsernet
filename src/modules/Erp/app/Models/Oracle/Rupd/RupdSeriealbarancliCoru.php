<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_SERIEALBARANCLI_CORU
 * Tabla de replicación/materialización de Oracle
 */
class RupdSeriealbarancliCoru extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_seriealbarancli_coru';

    protected $primaryKey = 'idseriealbarancli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
