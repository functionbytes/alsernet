<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CONDICIONPAGO
 * Tabla de replicación/materialización de Oracle
 */
class MlogCondicionpago extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_condicionpago';

    protected $primaryKey = 'idcondicionpago';

    public $incrementing = false;

    public $timestamps = false;
}
