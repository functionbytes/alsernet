<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_PORTES
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_WPORTES_WPORTES_TIPO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: TIPO
 *
 * PK_W_PORTES (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WPortes extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_portes';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'codigo', 'importe', 'acumulable', 'tipo', 'estado',
        'idusuariocre', 'idusuariomod', 'idusuariobaja',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con WPortesTipo
     */
    public function wPortesTipo()
    {
        return $this->belongsTo(WPortesTipo::class, 'tipo', 'idw_portes_tipo');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_PORTES (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(WAyudas::class, 'id', 'id');
    }
}
