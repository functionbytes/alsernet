<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_ARTICULOIDIOMA
 * Tabla de replicación/materialización de Oracle
 */
class MlogArticuloidioma extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_articuloidioma';

    protected $primaryKey = 'idarticuloidioma';

    public $incrementing = false;

    public $timestamps = false;
}
