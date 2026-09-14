<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SERIEPEDIDOPRO_CENTR
 * Tabla de replicación/materialización de Oracle
 */
class MlogSeriepedidoproCentr extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_seriepedidopro_centr';

    protected $primaryKey = 'idseriepedidopro_central';

    public $incrementing = false;

    public $timestamps = false;
}
