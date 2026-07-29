<?php

namespace Modules\Erp\Models\Oracle\Promocion;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LIQUIDACIONBONO
 *
 * ÍNDICES DISPONIBLES:
 * PK_IDLIQUIDACIONBONO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLIQUIDACIONBONO
 */
class Liquidacionbono extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'liquidacionbono';

    protected $primaryKey = 'idliquidacionbono';

    public $timestamps = false;

    protected $fillable = [
        'fliquidacion',
    ];

    protected $casts = [
        'fliquidacion' => 'datetime',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Liquidacionbono
     * ✅ Usa PK_IDLIQUIDACIONBONO (indexado)
     */
    public function liquidacionbono()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Promocion\Liquidacionbono::class, 'idliquidacionbono', 'idliquidacionbono');
    }
}
