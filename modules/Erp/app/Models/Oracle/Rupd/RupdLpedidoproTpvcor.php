<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPEDIDOPRO_TPVCOR
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpedidoproTpvcor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpedidopro_tpvcor';

    protected $primaryKey = 'idlpedidopro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
