<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla PERIODO_CUOTA
 *
 * ÍNDICES DISPONIBLES:
 * PK_PERIODO_CUOTA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPERIODO_CUOTA
 */
class PeriodoCuota extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'periodo_cuota';

    protected $primaryKey = 'idperiodo_cuota';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'meses', 'vencido', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
        'estado', 'descripcion',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: PeriodoCuota
     * ✅ Usa PK_PERIODO_CUOTA (indexado)
     */
    public function periodoCuota()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\PeriodoCuota::class, 'idperiodo_cuota', 'idperiodo_cuota');
    }
}
