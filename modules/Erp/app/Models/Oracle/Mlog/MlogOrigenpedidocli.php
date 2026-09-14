<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_ORIGENPEDIDOCLI
 * Tabla de replicación/materialización de Oracle
 */
class MlogOrigenpedidocli extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_origenpedidocli';

    protected $primaryKey = 'idorigenpedidocli';

    public $incrementing = false;

    public $timestamps = false;
}
