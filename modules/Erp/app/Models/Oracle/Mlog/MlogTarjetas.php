<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TARJETAS
 * Tabla de replicación/materialización de Oracle
 */
class MlogTarjetas extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tarjetas';

    protected $primaryKey = 'idtarjeta';

    public $incrementing = false;

    public $timestamps = false;
}
