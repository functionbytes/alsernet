<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_OBJETO
 * Tabla de replicación/materialización de Oracle
 */
class MlogObjeto extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_objeto';

    protected $primaryKey = 'idobjeto';

    public $incrementing = false;

    public $timestamps = false;
}
