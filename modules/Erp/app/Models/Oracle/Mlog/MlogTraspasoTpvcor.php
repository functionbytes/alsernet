<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TRASPASO_TPVCOR
 * Tabla de replicación/materialización de Oracle
 */
class MlogTraspasoTpvcor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_traspaso_tpvcor';

    protected $primaryKey = 'idtraspaso';

    public $incrementing = false;

    public $timestamps = false;
}
