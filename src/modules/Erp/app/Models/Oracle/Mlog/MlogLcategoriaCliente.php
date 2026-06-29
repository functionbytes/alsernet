<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LCATEGORIA_CLIENTE
 * Tabla de replicación/materialización de Oracle
 */
class MlogLcategoriaCliente extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lcategoria_cliente';

    protected $primaryKey = 'idlcategoria_cliente';

    public $incrementing = false;

    public $timestamps = false;
}
