<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TTARJETAREGALO
 * Tabla de replicación/materialización de Oracle
 */
class MlogTtarjetaregalo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_ttarjetaregalo';

    protected $primaryKey = 'idttarjetaregalo';

    public $incrementing = false;

    public $timestamps = false;
}
