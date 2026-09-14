<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LPEDIDOPRO_SERVIDO_CAPTHAYA
 *
 * ÍNDICES DISPONIBLES:
 * PK_LPEDPRO_SERV_CAPTHAYA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLPEDIDOPRO_SERVIDO
 */
class LpedidoproServidoCapthaya extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lpedidopro_servido_capthaya';

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
     * ✅ Usa PK_LPEDPRO_SERV_CAPTHAYA (indexado)
     */
    public function lpedidoproServido()
    {
        return $this->belongsTo(LpedidoproServidoCapthaya::class, 'idlpedidopro_servido', 'idlpedidopro_servido');
    }

    /**
     * Relación: Lpedidopro
     * ⚠️  SIN ÍNDICE en IDLPEDIDOPRO
     */
    public function lpedidopro()
    {
        return $this->belongsTo(LpedidoproCapthaya::class, 'idlpedidopro', 'idlpedidopro');
    }
}
