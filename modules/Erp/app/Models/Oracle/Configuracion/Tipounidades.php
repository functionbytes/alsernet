<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TIPOUNIDADES
 *
 * ÍNDICES DISPONIBLES:
 * PK_TIPOUNIDADES (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTIPOUNIDADES
 */
class Tipounidades extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tipounidades';

    protected $primaryKey = 'idtipounidades';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'idusuariomod',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Tipounidades
     * ✅ Usa PK_TIPOUNIDADES (indexado)
     */
    public function tipounidades()
    {
        return $this->belongsTo(Tipounidades::class, 'idtipounidades', 'idtipounidades');
    }
}
