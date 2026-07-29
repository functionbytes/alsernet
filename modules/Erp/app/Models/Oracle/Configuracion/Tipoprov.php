<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TIPOPROV
 *
 * ÍNDICES DISPONIBLES:
 * PK_TIPOPROV (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTIPOPROV
 */
class Tipoprov extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tipoprov';

    protected $primaryKey = 'idtipoprov';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'estado', 'descripcion', 'idusuariomod',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Tipoprov
     * ✅ Usa PK_TIPOPROV (indexado)
     */
    public function tipoprov()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Tipoprov::class, 'idtipoprov', 'idtipoprov');
    }
}
