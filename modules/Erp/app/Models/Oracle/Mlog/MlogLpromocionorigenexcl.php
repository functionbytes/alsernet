<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPROMOCIONORIGENEXCL
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpromocionorigenexcl extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpromocionorigenexcl';

    protected $primaryKey = 'idlpromocionorigenexcluido';

    public $incrementing = false;

    public $timestamps = false;
}
