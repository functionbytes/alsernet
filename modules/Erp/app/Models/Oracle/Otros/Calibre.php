<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CALIBRE
 *
 * ÍNDICES DISPONIBLES:
 * PK_CALIBRE (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCALIBRE
 */
class Calibre extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'calibre';

    protected $primaryKey = 'idcalibre';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'estado', 'descripcion', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Calibre
     * ✅ Usa PK_CALIBRE (indexado)
     */
    public function calibre()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Calibre::class, 'idcalibre', 'idcalibre');
    }
}
