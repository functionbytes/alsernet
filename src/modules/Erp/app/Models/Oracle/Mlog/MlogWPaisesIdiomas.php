<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_W_PAISES_IDIOMAS
 * Tabla de replicación/materialización de Oracle
 */
class MlogWPaisesIdiomas extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_w_paises_idiomas';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;
}
