<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_GENERACION_BONO_PROM
 * Tabla de replicación/materialización de Oracle
 */
class MlogGeneracionBonoProm extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_generacion_bono_prom';

    protected $primaryKey = 'idgeneracion_bono_promo';

    public $incrementing = false;

    public $timestamps = false;
}
