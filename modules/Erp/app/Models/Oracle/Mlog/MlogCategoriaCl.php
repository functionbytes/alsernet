<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CATEGORIA_CL
 * Tabla de replicación/materialización de Oracle
 */
class MlogCategoriaCl extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_categoria_cl';

    protected $primaryKey = 'idcategoria_cl';

    public $incrementing = false;

    public $timestamps = false;
}
