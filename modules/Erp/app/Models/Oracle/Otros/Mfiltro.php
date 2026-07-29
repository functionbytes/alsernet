<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla MFILTRO
 *
 * ÍNDICES DISPONIBLES:
 * PK_MFILTRO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDMFILTRO
 */
class Mfiltro extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'mfiltro';

    protected $primaryKey = 'idmfiltro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'estado', 'idusuariomod', 'nombre', 'descripcion',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Mfiltro
     * ✅ Usa PK_MFILTRO (indexado)
     */
    public function mfiltro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Mfiltro::class, 'idmfiltro', 'idmfiltro');
    }
}
