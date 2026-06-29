<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SERIEPEDIDOCLI_CAPTH
 * Tabla de replicación/materialización de Oracle
 */
class MlogSeriepedidocliCapth extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_seriepedidocli_capth';

    protected $primaryKey = 'idseriepedidocli';

    public $incrementing = false;

    public $timestamps = false;
}
