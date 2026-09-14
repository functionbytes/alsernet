<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LREBAJA
 * Tabla de replicación/materialización de Oracle
 */
class MlogLrebaja extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lrebaja';

    protected $primaryKey = 'idlrebaja';

    public $incrementing = false;

    public $timestamps = false;
}
