<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TMOVALM
 * Tabla de replicación/materialización de Oracle
 */
class MlogTmovalm extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tmovalm';

    protected $primaryKey = 'idtmovalm';

    public $incrementing = false;

    public $timestamps = false;
}
