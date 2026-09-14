<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_IMPORTACION_ARTICULO
 * Tabla de replicación/materialización de Oracle
 */
class MlogImportacionArticulo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_importacion_articulo';

    protected $primaryKey = 'idimportacion_articulo';

    public $incrementing = false;

    public $timestamps = false;
}
