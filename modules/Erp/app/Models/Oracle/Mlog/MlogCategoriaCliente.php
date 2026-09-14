<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CATEGORIA_CLIENTE
 * Tabla de replicación/materialización de Oracle
 */
class MlogCategoriaCliente extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_categoria_cliente';

    protected $primaryKey = 'idcategoria_cliente';

    public $incrementing = false;

    public $timestamps = false;
}
