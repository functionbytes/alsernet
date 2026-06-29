<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_FAMILIA_CL
 * Tabla de replicación/materialización de Oracle
 */
class MlogFamiliaCl extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_familia_cl';

    protected $primaryKey = 'idfamilia_cl';

    public $incrementing = false;

    public $timestamps = false;
}
