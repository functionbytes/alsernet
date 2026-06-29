<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TAG_X_TABLA
 * Tabla de replicación/materialización de Oracle
 */
class MlogTagXTabla extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tag_x_tabla';

    protected $primaryKey = 'idtag_x_tabla';

    public $incrementing = false;

    public $timestamps = false;
}
