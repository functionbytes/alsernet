<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TARJETAS
 * Tabla de replicación/materialización de Oracle
 */
class RupdTarjetas extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tarjetas';

    protected $primaryKey = 'idtarjeta';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
