<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CLIENTEDIRECCION_CEN
 * Tabla de replicación/materialización de Oracle
 */
class MlogClientedireccionCen extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_clientedireccion_cen';

    protected $primaryKey = 'idclientedireccion';

    public $incrementing = false;

    public $timestamps = false;
}
