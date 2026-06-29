<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPEDIDOPRO_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpedidoproMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpedidopro_monte2';

    protected $primaryKey = 'idlpedidopro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
