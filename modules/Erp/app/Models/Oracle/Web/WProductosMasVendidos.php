<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_PRODUCTOS_MAS_VENDIDOS
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_W_PRODUCTOS_MAS_VENDIDOS_I (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_MODELO
 *
 * PK_W_PRODUCTOS_MAS_VENDIDOS (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WProductosMasVendidos extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_productos_mas_vendidos';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id_tienda', 'id_modelo', 'idmodelo',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con WModelo
     */
    public function _modelo()
    {
        return $this->belongsTo(WModelo::class, 'id_modelo', 'idw_modelo');
    }

    /**
     * Relación: Modelo
     * ✅ Usa IDX_W_PRODUCTOS_MAS_VENDIDOS_I (indexado)
     */
    public function modelo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WModelo::class, 'id_modelo', 'id');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_PRODUCTOS_MAS_VENDIDOS (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id', 'id');
    }
}
