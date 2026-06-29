<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LCATEGORIA_CLIENTE
 * Tabla de replicación/materialización de Oracle
 */
class RupdLcategoriaCliente extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lcategoria_cliente';

    protected $primaryKey = 'idlcategoria_cliente';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
