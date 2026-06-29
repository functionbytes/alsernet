<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_W_VALORES_NAV_IDIOMA
 * Tabla de replicación/materialización de Oracle
 */
class MlogWValoresNavIdioma extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_w_valores_nav_idioma';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;
}
