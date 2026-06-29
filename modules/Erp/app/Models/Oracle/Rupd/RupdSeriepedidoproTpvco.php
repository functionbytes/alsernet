<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_SERIEPEDIDOPRO_TPVCO
 * Tabla de replicación/materialización de Oracle
 */
class RupdSeriepedidoproTpvco extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_seriepedidopro_tpvco';

    protected $primaryKey = 'idseriepedidopro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
