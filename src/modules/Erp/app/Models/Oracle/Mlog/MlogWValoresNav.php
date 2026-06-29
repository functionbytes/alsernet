<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_W_VALORES_NAV
 * Tabla de replicación/materialización de Oracle
 */
class MlogWValoresNav extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_w_valores_nav';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;
}
