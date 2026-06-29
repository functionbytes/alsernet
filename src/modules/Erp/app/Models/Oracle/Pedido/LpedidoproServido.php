<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LPEDIDOPRO_SERVIDO_TPVCOR
 *
 * ÍNDICES DISPONIBLES:
 * PK_LPEDPRO_SERV_TPVCOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLPEDIDOPRO_SERVIDO
 */
class LpedidoproServido extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lpedidopro_servido_tpvcor';

    protected $primaryKey = 'idlpedidopro_servido';

    public $timestamps = false;

    protected $fillable = [
        'idlpedidopro', 'unidades_servidas', 'not',
    ];
}
