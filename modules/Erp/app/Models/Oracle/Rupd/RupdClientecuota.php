<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CLIENTECUOTA
 * Tabla de replicación/materialización de Oracle
 */
class RupdClientecuota extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_clientecuota';

    protected $primaryKey = 'idclientecuota';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
