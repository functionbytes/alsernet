<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CLIENTETELEFONO_CENT
 * Tabla de replicación/materialización de Oracle
 */
class MlogClientetelefonoCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_clientetelefono_cent';

    protected $primaryKey = 'idclientetelefono';

    public $incrementing = false;

    public $timestamps = false;
}
