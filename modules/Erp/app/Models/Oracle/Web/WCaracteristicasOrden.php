<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_CARACTERISTICAS_ORDEN
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_WCARACT_ORDEN_WCARACTP (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_CARACTERISTICA
 *
 * ✅ IDX_WCARACT_ORDEN_WMODELO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_MODELO
 *
 * PK_W_CARACTERISTICAS_ORDEN (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WCaracteristicasOrden extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_caracteristicas_orden';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'id_caracteristica', 'id_modelo', 'orden', 'estado', 'idusuariocre',
        'idusuariomod', 'idusuariobaja', 'idmodelo',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con WCaracteristicasProd
     */
    public function _caracteristica()
    {
        return $this->belongsTo(WCaracteristicasProd::class, 'id_caracteristica', 'idw_caracteristicas_prod');
    }

    /**
     * Relación con WModelo
     */
    public function _modelo()
    {
        return $this->belongsTo(WModelo::class, 'id_modelo', 'idw_modelo');
    }

    /**
     * Relación: Caracteristica
     * ✅ Usa IDX_WCARACT_ORDEN_WCARACTP (indexado)
     */
    public function caracteristica()
    {
        return $this->belongsTo(WCaracteristicasProd::class, 'id_caracteristica', 'id');
    }

    /**
     * Relación: Modelo
     * ✅ Usa IDX_WCARACT_ORDEN_WMODELO (indexado)
     */
    public function modelo()
    {
        return $this->belongsTo(WModelo::class, 'id_modelo', 'id');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_CARACTERISTICAS_ORDEN (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(WAyudas::class, 'id', 'id');
    }
}
