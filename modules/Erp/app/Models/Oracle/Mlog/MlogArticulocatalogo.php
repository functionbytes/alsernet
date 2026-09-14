<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_ARTICULOCATALOGO
 * Tabla de replicación/materialización de Oracle
 */
class MlogArticulocatalogo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_articulocatalogo';

    protected $primaryKey = 'idarticulocatalogo';

    public $incrementing = false;

    public $timestamps = false;
}
