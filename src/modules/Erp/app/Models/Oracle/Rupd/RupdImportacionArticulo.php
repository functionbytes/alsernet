<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_IMPORTACION_ARTICULO
 * Tabla de replicación/materialización de Oracle
 */
class RupdImportacionArticulo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_importacion_articulo';

    protected $primaryKey = 'idimportacion_articulo';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
