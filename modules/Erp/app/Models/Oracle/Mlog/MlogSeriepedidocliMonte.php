<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SERIEPEDIDOCLI_MONTE
 * Tabla de replicación/materialización de Oracle
 */
class MlogSeriepedidocliMonte extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_seriepedidocli_monte';

    protected $primaryKey = 'idseriepedidocli';

    public $incrementing = false;

    public $timestamps = false;
}
