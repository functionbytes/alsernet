<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_REBAJA
 * Tabla de replicación/materialización de Oracle
 */
class MlogRebaja extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_rebaja';

    protected $primaryKey = 'idrebaja';

    public $incrementing = false;

    public $timestamps = false;
}
