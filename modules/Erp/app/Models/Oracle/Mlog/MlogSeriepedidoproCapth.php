<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SERIEPEDIDOPRO_CAPTH
 * Tabla de replicación/materialización de Oracle
 */
class MlogSeriepedidoproCapth extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_seriepedidopro_capth';

    protected $primaryKey = 'idseriepedidopro';

    public $incrementing = false;

    public $timestamps = false;
}
