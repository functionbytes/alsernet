<?php

namespace Modules\Erp\Models\Oracle\Catalogo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla FOTOGRAFIA
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_FOTOGRAFIA_IDTIPOFOTOGRAFI (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTIPOFOTOGRAFIA
 *
 * PK_IDFOTOGRAFIA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFOTOGRAFIA
 */
class Fotografia extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'fotografia';

    protected $primaryKey = 'idfotografia';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'estado', 'idtipofotografia', 'imagen',
        'glyph', 'original', 'nombre',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Tipofotografia
     */
    public function tipofotografia()
    {
        return $this->belongsTo(Tipofotografia::class, 'idtipofotografia', 'idtipofotografia');
    }

    /**
     * Relación: Fotografia
     * ✅ Usa PK_IDFOTOGRAFIA (indexado)
     */
    public function fotografia()
    {
        return $this->belongsTo(Fotografia::class, 'idfotografia', 'idfotografia');
    }
}
