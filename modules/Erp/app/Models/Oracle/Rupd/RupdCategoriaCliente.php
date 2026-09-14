<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CATEGORIA_CLIENTE
 * Tabla de replicación/materialización de Oracle
 */
class RupdCategoriaCliente extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_categoria_cliente';

    protected $primaryKey = 'idcategoria_cliente';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
