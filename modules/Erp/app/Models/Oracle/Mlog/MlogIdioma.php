<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_IDIOMA
 * Tabla de replicación/materialización de Oracle
 */
class MlogIdioma extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_idioma';

    protected $primaryKey = 'ididioma';

    public $incrementing = false;

    public $timestamps = false;
}
