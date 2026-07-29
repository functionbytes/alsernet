<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_TIENDAS_IDIOMAS
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_W_TIENDAS_IDIOMAS_ID_TIEND (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_TIENDA
 *
 * PK_W_TIENDAS_IDIOMAS (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WTiendasIdiomas extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_tiendas_idiomas';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id_tienda', 'nombre', 'idioma',
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
     * Relación: Tienda
     * ✅ Usa IDX_W_TIENDAS_IDIOMAS_ID_TIEND (indexado)
     */
    public function tienda()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WTiendas::class, 'id_tienda', 'id');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_TIENDAS_IDIOMAS (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id', 'id');
    }
}
