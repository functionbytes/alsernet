<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla MONEDA
 *
 * ÍNDICES DISPONIBLES:
 * PK_MONEDA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDMONEDA
 */
class Moneda extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'moneda';

    protected $primaryKey = 'idmonedaantiguo';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idmoneda', 'descripcion', 'codigo', 'simbolo', 'estado',
        'factorconversioneuro', 'mascara', 'idusuariomod',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Moneda
     * ✅ Usa PK_MONEDA (indexado)
     */
    public function moneda()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Moneda::class, 'idmoneda', 'idmoneda');
    }
}
