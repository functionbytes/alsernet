<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TIPOART
 *
 * ÍNDICES DISPONIBLES:
 * PK_TIPOART (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTIPOART
 */
class Tipoart extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tipoart';

    protected $primaryKey = 'idtipoart';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'estado', 'idusuariomod',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Tipoart
     * ✅ Usa PK_TIPOART (indexado)
     */
    public function tipoart()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Tipoart::class, 'idtipoart', 'idtipoart');
    }
}
