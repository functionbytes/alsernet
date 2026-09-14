<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_FPPEDCLI_CAPTHAYA
 * Tabla de replicación/materialización de Oracle
 */
class RupdFppedcliCapthaya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_fppedcli_capthaya';

    protected $primaryKey = 'idfppedcli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
