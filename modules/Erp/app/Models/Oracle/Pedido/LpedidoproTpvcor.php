<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LPEDIDOPRO_TPVCOR
 *
 * ÍNDICES DISPONIBLES:
 * PK_LPEDIDOPRO_TPVCOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLPEDIDOPRO
 */
class LpedidoproTpvcor extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lpedidopro_tpvcor';

    protected $primaryKey = 'idlpedidopro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    protected $fillable = [
        'idpedidopro', 'idarticulo', 'fminentrega', 'fmaxentrega', 'unidades',
        'not', 'precio', 'dto', 'tipo', 'idusuariomod',
        'idtipomedida', 'unid', 'iva', 'recargo', 'idlpedidocli',
        'notapieza', 'dto2', 'preciomonedaoriginal', 'idlpropuestapro', 'unidades_recomendadas',
        'unidades_originales',
    ];

    protected $casts = [
        'fminentrega' => 'datetime',
        'fmaxentrega' => 'datetime',
    ];
}
