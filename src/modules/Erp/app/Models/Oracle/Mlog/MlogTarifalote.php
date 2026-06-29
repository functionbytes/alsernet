<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TARIFALOTE
 * Tabla de replicación/materialización de Oracle
 */
class MlogTarifalote extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tarifalote';

    protected $primaryKey = 'idtarifalote';

    public $incrementing = false;

    public $timestamps = false;
}
