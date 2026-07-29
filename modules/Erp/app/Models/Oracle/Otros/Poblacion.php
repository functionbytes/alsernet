<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla POBLACION
 *
 * ÍNDICES DISPONIBLES:
 * PK_POBLACION (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPOBLACION
 */
class Poblacion extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'poblacion';

    protected $primaryKey = 'idpoblacion';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'idprovincia', 'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Poblacion
     * ✅ Usa PK_POBLACION (indexado)
     */
    public function poblacion()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Poblacion::class, 'idpoblacion', 'idpoblacion');
    }

    /**
     * Relación: Provincia
     * ⚠️  SIN ÍNDICE en IDPROVINCIA
     */
    public function provincia()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Provincia::class, 'idprovincia', 'idprovincia');
    }
}
