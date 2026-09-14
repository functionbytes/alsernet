<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LGENERACION_BONO_PRO
 * Tabla de replicación/materialización de Oracle
 */
class RupdLgeneracionBonoPro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lgeneracion_bono_pro';

    protected $primaryKey = 'idlgeneracion_bono_promo';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
