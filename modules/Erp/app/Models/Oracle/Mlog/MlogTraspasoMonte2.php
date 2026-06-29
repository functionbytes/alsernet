<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TRASPASO_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class MlogTraspasoMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_traspaso_monte2';

    protected $primaryKey = 'idtraspaso';

    public $incrementing = false;

    public $timestamps = false;
}
