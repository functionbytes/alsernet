<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_ARTICULOSTOCKMINMAX
 * Tabla de replicación/materialización de Oracle
 */
class MlogArticulostockminmax extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_articulostockminmax';

    protected $primaryKey = 'idarticulostockminmax';

    public $incrementing = false;

    public $timestamps = false;
}
