<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SERIEPEDIDOCLI_DDLEO
 * Tabla de replicación/materialización de Oracle
 */
class MlogSeriepedidocliDdleo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_seriepedidocli_ddleo';

    protected $primaryKey = 'idseriepedidocli';

    public $incrementing = false;

    public $timestamps = false;
}
