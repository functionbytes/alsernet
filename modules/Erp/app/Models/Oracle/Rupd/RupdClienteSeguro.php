<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CLIENTE_SEGURO
 * Tabla de replicación/materialización de Oracle
 */
class RupdClienteSeguro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_cliente_seguro';

    protected $primaryKey = 'idcliente_seguro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
