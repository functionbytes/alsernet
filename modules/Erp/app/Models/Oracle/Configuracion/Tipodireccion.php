<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TIPODIRECCION
 *
 * ÍNDICES DISPONIBLES:
 * PK_TIPODIRECCION (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTIPODIRECCION
 */
class Tipodireccion extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tipodireccion';

    protected $primaryKey = 'idtipodireccion';

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
     * Relación: Tipodireccion
     * ✅ Usa PK_TIPODIRECCION (indexado)
     */
    public function tipodireccion()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Tipodireccion::class, 'idtipodireccion', 'idtipodireccion');
    }
}
