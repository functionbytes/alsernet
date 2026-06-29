<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_FOTOGRAFIA
 * Tabla de replicación/materialización de Oracle
 */
class MlogFotografia extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_fotografia';

    protected $primaryKey = 'idfotografia';

    public $incrementing = false;

    public $timestamps = false;
}
