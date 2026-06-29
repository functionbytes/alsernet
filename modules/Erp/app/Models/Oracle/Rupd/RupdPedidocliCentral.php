<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_PEDIDOCLI_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class RupdPedidocliCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_pedidocli_central';

    protected $primaryKey = 'idpedidocli_central';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
