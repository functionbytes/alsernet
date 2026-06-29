<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_W_TIENDAS_IDIOMAS
 * Tabla de replicación/materialización de Oracle
 */
class MlogWTiendasIdiomas extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_w_tiendas_idiomas';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;
}
