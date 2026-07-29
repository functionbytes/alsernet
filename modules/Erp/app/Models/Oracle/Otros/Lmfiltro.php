<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LMFILTRO
 *
 * ÍNDICES DISPONIBLES:
 * PK_LMFILTRO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLMFILTRO
 */
class Lmfiltro extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lmfiltro';

    protected $primaryKey = 'idlmfiltro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idtipo', 'idmfiltro', 'idcampo', 'estado', 'idusuariomod',
        'descripcion', 'visible', 'codigo', 'tipo', 'longitud',
        'decimales', 'sufijo', 'orden', 'mostrarlookup', 'modolike',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Lmfiltro
     * ✅ Usa PK_LMFILTRO (indexado)
     */
    public function lmfiltro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Lmfiltro::class, 'idlmfiltro', 'idlmfiltro');
    }

    /**
     * Relación: Mfiltro
     * ⚠️  SIN ÍNDICE en IDMFILTRO
     */
    public function mfiltro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Mfiltro::class, 'idmfiltro', 'idmfiltro');
    }

    /**
     * Relación: Campo
     * ⚠️  SIN ÍNDICE en IDCAMPO
     */
    public function campo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Campo::class, 'idcampo', 'idcampo');
    }
}
