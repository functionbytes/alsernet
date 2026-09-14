<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_PORTES_TIPO
 *
 * ÍNDICES DISPONIBLES:
 * PK_W_PORTES_TIPO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WPortesTipo extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_portes_tipo';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'tipo_es', 'plazo_es', 'tipo_en', 'plazo_en', 'estado',
        'idusuariocre', 'idusuariomod', 'idusuariobaja',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con WPortes
     */
    public function wPortes()
    {
        return $this->hasMany(WPortes::class, 'tipo', 'idw_portes_tipo');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_PORTES_TIPO (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(WAyudas::class, 'id', 'id');
    }
}
