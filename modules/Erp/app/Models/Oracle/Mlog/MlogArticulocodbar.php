<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_ARTICULOCODBAR
 * Tabla de replicación/materialización de Oracle
 */
class MlogArticulocodbar extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_articulocodbar';

    protected $primaryKey = 'idarticulocodbar';

    public $incrementing = false;

    public $timestamps = false;
}
