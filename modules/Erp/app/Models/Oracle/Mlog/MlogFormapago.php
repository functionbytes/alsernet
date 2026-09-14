<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_FORMAPAGO
 * Tabla de replicación/materialización de Oracle
 */
class MlogFormapago extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_formapago';

    protected $primaryKey = 'idformapago';

    public $incrementing = false;

    public $timestamps = false;
}
