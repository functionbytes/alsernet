<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CLIENTECUENTA_CENT
 * Tabla de replicación/materialización de Oracle
 */
class RupdClientecuentaCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_clientecuenta_cent';

    protected $primaryKey = 'idclientecuenta';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
