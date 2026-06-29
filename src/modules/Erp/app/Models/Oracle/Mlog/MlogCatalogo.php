<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CATALOGO
 * Tabla de replicación/materialización de Oracle
 */
class MlogCatalogo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_catalogo';

    protected $primaryKey = 'idcatalogo';

    public $incrementing = false;

    public $timestamps = false;
}
