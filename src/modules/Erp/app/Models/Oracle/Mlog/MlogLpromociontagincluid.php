<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPROMOCIONTAGINCLUID
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpromociontagincluid extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpromociontagincluid';

    protected $primaryKey = 'idlpromociontagincluido';

    public $incrementing = false;

    public $timestamps = false;
}
