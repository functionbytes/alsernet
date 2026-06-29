<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_ARTICULO_CATALOGO_IM
 * Tabla de replicación/materialización de Oracle
 */
class RupdArticuloCatalogoIm extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_articulo_catalogo_im';

    protected $primaryKey = 'idarticulo_catalogoimpreso';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
