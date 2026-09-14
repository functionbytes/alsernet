<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_PEDIDOPRO_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class RupdPedidoproCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_pedidopro_central';

    protected $primaryKey = 'idpedidopro_central';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
