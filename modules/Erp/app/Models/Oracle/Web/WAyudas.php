<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_AYUDAS
 *
 * ÍNDICES DISPONIBLES:
 * PK_W_AYUDAS (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WAyudas extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_ayudas';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'titulo', 'texto', 'idioma', 'orden', 'activo',
        'portal', 'enlace', 'estado', 'idusuariocre', 'idusuariomod',
        'idusuariobaja',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con WAyudasMod
     */
    public function wAyudasMods()
    {
        return $this->hasMany(WAyudasMod::class, 'id_ayuda', 'idw_ayudas');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_AYUDAS (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(WAyudas::class, 'id', 'id');
    }
}
