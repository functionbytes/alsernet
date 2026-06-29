<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TTARIFA
 * Tabla de replicación/materialización de Oracle
 */
class MlogTtarifa extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_ttarifa';

    protected $primaryKey = 'idttarifa';

    public $incrementing = false;

    public $timestamps = false;
}
