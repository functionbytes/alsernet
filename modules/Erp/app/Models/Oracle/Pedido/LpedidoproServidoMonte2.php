<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LPEDIDOPRO_SERVIDO_MONTE2
 *
 * ÍNDICES DISPONIBLES:
 * PK_LPEDPRO_SERV_MONTE2 (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLPEDIDOPRO_SERVIDO
 */
class LpedidoproServidoMonte2 extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lpedidopro_servido_monte2';

    protected $primaryKey = 'idlpedidopro_servido';

    public $timestamps = false;

    protected $fillable = [
        'idlpedidopro', 'unidades_servidas', 'not',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: LpedidoproServido
     * ✅ Usa PK_LPEDPRO_SERV_MONTE2 (indexado)
     */
    public function lpedidoproServido()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\LpedidoproServidoCapthaya::class, 'idlpedidopro_servido', 'idlpedidopro_servido');
    }

    /**
     * Relación: Lpedidopro
     * ⚠️  SIN ÍNDICE en IDLPEDIDOPRO
     */
    public function lpedidopro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\LpedidoproCapthaya::class, 'idlpedidopro', 'idlpedidopro');
    }
}
