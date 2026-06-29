<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TBONO_PROMOCION
 * Tabla de replicación/materialización de Oracle
 */
class RupdTbonoPromocion extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tbono_promocion';

    protected $primaryKey = 'idtbono_promocion';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
