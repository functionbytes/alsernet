<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_MFILTRO
 * Tabla de replicación/materialización de Oracle
 */
class MlogMfiltro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_mfiltro';

    protected $primaryKey = 'idmfiltro';

    public $incrementing = false;

    public $timestamps = false;
}
