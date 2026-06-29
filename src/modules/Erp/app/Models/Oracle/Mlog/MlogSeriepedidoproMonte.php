<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SERIEPEDIDOPRO_MONTE
 * Tabla de replicación/materialización de Oracle
 */
class MlogSeriepedidoproMonte extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_seriepedidopro_monte';

    protected $primaryKey = 'idseriepedidopro';

    public $incrementing = false;

    public $timestamps = false;
}
