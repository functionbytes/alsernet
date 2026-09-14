<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPROMOCIONSUBFAMILIA
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpromocionsubfamilia extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpromocionsubfamilia';

    protected $primaryKey = 'idlpromocionsubfamiliaincluida';

    public $incrementing = false;

    public $timestamps = false;
}
