<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SEGURO
 * Tabla de replicación/materialización de Oracle
 */
class MlogSeguro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_seguro';

    protected $primaryKey = 'idseguro';

    public $incrementing = false;

    public $timestamps = false;
}
