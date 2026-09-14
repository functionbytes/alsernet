<?php

namespace Modules\Erp\Models\Oracle\Factura;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LFACTURACLI_CENTRAL
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_FACTURACLICENT_IDLALBCENT (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLALBARANCLI_CENTRAL
 *
 * ✅ INDX_LFACTURACLI_IDFACLI (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFACTURACLI
 *
 * PK_LFACTURACLICENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLFACTURACLI
 */
class Lfacturacli extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lfacturacli_central';

    protected $primaryKey = 'idlfacturacli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    protected $fillable = [
        'idfacturacli', 'idlalbarancli', 'idarticulo', 'unidades', 'not',
        'iva', 'not', 'recargo', 'not', 'pbi',
        'not', 'dto', 'not', 'idusuariomod', 'codigo',
        'descripcion', 'dtocabecera', 'idtipomedida', 'unid', 'idlpedidocli',
        'idlote', 'seclote', 'total_bi', 'total_con_impuestos', 'idalmacen',
        'idlalbarancli_central', 'ngrupo_segundamano', 'parte_exenta', 'not', 'nexpediente',
        'fexpediente', 'numero_serie',
    ];

    protected $casts = [
        'fexpediente' => 'datetime',
    ];
}
