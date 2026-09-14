<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CATALOGOIDIOMA
 * Tabla de replicación/materialización de Oracle
 */
class MlogCatalogoidioma extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_catalogoidioma';

    protected $primaryKey = 'idcatalogoidioma';

    public $incrementing = false;

    public $timestamps = false;
}
