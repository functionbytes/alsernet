<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SUBFAMILIA_CL
 * Tabla de replicación/materialización de Oracle
 */
class MlogSubfamiliaCl extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_subfamilia_cl';

    protected $primaryKey = 'idsubfamilia_cl';

    public $incrementing = false;

    public $timestamps = false;
}
