<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_DIARIO_CENT
 * Tabla de replicación/materialización de Oracle
 */
class MlogDiarioCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_diario_cent';

    protected $primaryKey = 'iddiario';

    public $incrementing = false;

    public $timestamps = false;
}
