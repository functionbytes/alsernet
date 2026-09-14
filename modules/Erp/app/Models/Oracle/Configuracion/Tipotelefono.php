<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TIPOTELEFONO
 *
 * ÍNDICES DISPONIBLES:
 * PK_TIPOTELEFONO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTIPOTELEFONO
 */
class Tipotelefono extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tipotelefono';

    protected $primaryKey = 'idtipotelefono';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'idusuariocre', 'idusuariomod', 'idusuariobaja', 'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Tipotelefono
     * ✅ Usa PK_TIPOTELEFONO (indexado)
     */
    public function tipotelefono()
    {
        return $this->belongsTo(Tipotelefono::class, 'idtipotelefono', 'idtipotelefono');
    }
}
