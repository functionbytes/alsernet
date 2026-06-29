<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_SERIEALBARANCLI_TPVC
 * Tabla de replicación/materialización de Oracle
 */
class RupdSeriealbarancliTpvc extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_seriealbarancli_tpvc';

    protected $primaryKey = 'idseriealbarancli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
