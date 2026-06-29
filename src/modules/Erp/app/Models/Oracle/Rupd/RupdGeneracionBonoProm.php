<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_GENERACION_BONO_PROM
 * Tabla de replicación/materialización de Oracle
 */
class RupdGeneracionBonoProm extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_generacion_bono_prom';

    protected $primaryKey = 'idgeneracion_bono_promo';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
