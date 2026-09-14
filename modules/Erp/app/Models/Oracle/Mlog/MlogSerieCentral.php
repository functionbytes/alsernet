<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SERIE_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogSerieCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_serie_central';

    protected $primaryKey = 'idserie';

    public $incrementing = false;

    public $timestamps = false;
}
