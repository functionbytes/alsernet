<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_MARCA
 * Tabla de replicación/materialización de Oracle
 */
class MlogMarca extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_marca';

    protected $primaryKey = 'idmarca';

    public $incrementing = false;

    public $timestamps = false;
}
