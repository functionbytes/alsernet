<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_DESCUENTOS_RELACIONADOS
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_W_DESCUENTOS_RELACIONADOS_ (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_TIENDA
 *
 * PK_W_DTO_RELACIONADOS (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WDescuentosRelacionados extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_descuentos_relacionados';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id_tienda', 'descuento',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con WTiendas
     */
    public function _tienda()
    {
        return $this->belongsTo(WTiendas::class, 'id_tienda', 'idw_tiendas');
    }

    /**
     * Relación inversa con WDescuentosRelacionValor
     */
    public function wDescuentosRelacionValors()
    {
        return $this->hasMany(WDescuentosRelacionValor::class, 'id_descuento_relacionado', 'idw_descuentos_relacionados');
    }

    /**
     * Relación: Tienda
     * ✅ Usa IDX_W_DESCUENTOS_RELACIONADOS_ (indexado)
     */
    public function tienda()
    {
        return $this->belongsTo(WTiendas::class, 'id_tienda', 'id');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_DTO_RELACIONADOS (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(WAyudas::class, 'id', 'id');
    }
}
