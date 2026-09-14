<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LGENERACION_BONO_PRO
 * Tabla de replicación/materialización de Oracle
 */
class MlogLgeneracionBonoPro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lgeneracion_bono_pro';

    protected $primaryKey = 'idlgeneracion_bono_promo';

    public $incrementing = false;

    public $timestamps = false;
}
