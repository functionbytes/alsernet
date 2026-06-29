<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla PEDIDOPRO_TPVCOR
 *
 * ÍNDICES DISPONIBLES:
 * PK_PEDIDOPRO_TPVCOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPEDIDOPRO
 */
class Pedidopro extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'pedidopro_tpvcor';

    protected $primaryKey = 'idpedidopro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    protected $fillable = [
        'idproveedor', 'fminentrega', 'fmaxentrega', 'portes', 'estado',
        'dto', 'npedidopro', 'fpedido', 'idalmacen', 'idusuariomod',
        'idseriepedidopro', 'npedido', 'idempleado', 'idregfiscal', 'observaciones',
        'idtipopedidoprov', 'tipopedido', 'idconversionmoneda', 'estpowerpick',
    ];

    protected $casts = [
        'fminentrega' => 'datetime',
        'fmaxentrega' => 'datetime',
        'fpedido' => 'datetime',
        'estado' => 'boolean',
    ];
}
