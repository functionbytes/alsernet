<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_PEDIDOPRO_CORUNYA
 * Tabla de replicación/materialización de Oracle
 */
class RupdPedidoproCorunya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_pedidopro_corunya';

    protected $primaryKey = 'idpedidopro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
