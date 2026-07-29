<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla FRCAMPO
 *
 * ÍNDICES DISPONIBLES:
 * PK_FRCAMPO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: CAM_IDCAMPO, IDCAMPO
 */
class Frcampo extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'frcampo';

    protected $primaryKey = 'cam_idcampo';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'idcampo',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Campo
     * ⚠️  SIN ÍNDICE en IDCAMPO
     */
    public function campo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Campo::class, 'idcampo', 'idcampo');
    }
}
