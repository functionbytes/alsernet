<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_NAVEGACION
 *
 * ÍNDICES DISPONIBLES:
 * PK_W_NAVEGACION (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WNavegacion extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_navegacion';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'id_padre', 'elemento', 'orden', 'url', 'imagen',
        'descripcion', 'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaja',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_NAVEGACION (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id', 'id');
    }
}
