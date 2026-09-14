<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPEDIDOPRO_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpedidoproCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpedidopro_central';

    protected $primaryKey = 'idlpedidopro_central';

    public $incrementing = false;

    public $timestamps = false;
}
