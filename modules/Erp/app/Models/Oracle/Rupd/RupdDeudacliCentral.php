<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_DEUDACLI_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class RupdDeudacliCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_deudacli_central';

    protected $primaryKey = 'iddeudacli_central';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
