<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPEDIDOPRO_SERVIDO_C2
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpedidoproServidoC2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpedidopro_servido_c2';

    protected $primaryKey = 'idlpedidopro_servido';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
