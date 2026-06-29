<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TBONO_PROMOCION
 * Tabla de replicación/materialización de Oracle
 */
class MlogTbonoPromocion extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tbono_promocion';

    protected $primaryKey = 'idtbono_promocion';

    public $incrementing = false;

    public $timestamps = false;
}
