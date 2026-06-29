<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_PEDIDOPRO_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class RupdPedidoproMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_pedidopro_monte2';

    protected $primaryKey = 'idpedidopro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
