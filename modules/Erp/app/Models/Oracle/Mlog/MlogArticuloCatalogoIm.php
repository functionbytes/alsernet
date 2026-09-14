<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_ARTICULO_CATALOGO_IM
 * Tabla de replicación/materialización de Oracle
 */
class MlogArticuloCatalogoIm extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_articulo_catalogo_im';

    protected $primaryKey = 'idarticulo_catalogoimpreso';

    public $incrementing = false;

    public $timestamps = false;
}
