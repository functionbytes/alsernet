<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CONVERSIONMONEDA
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_CONVERSIONMONEDA_FCREACION (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: FCREACION
 *
 * ✅ IDX_CONVERSIONMONEDA_IDMONEDA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDMONEDA
 *
 * PK_CONVERSIONMONEDA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCONVERSIONMONEDA
 */
class Conversionmoneda extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'conversionmoneda';

    protected $primaryKey = 'idconversionmoneda';

    public $timestamps = false;

    protected $fillable = [
        'idmoneda', 'factorconversionaeuros', 'not', 'idusuariocre',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Conversionmoneda
     * ✅ Usa PK_CONVERSIONMONEDA (indexado)
     */
    public function conversionmoneda()
    {
        return $this->belongsTo(Conversionmoneda::class, 'idconversionmoneda', 'idconversionmoneda');
    }

    /**
     * Relación: Moneda
     * ✅ Usa IDX_CONVERSIONMONEDA_IDMONEDA (indexado)
     */
    public function moneda()
    {
        return $this->belongsTo(Moneda::class, 'idmoneda', 'idmoneda');
    }
}
