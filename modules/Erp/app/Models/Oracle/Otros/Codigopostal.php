<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CODIGOPOSTAL
 *
 * ÍNDICES DISPONIBLES:
 * PK_CODIGOPOSTAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCODIGOPOSTAL
 */
class Codigopostal extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'codigopostal';

    protected $primaryKey = 'idcodigopostal';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'codigo', 'idpoblacion', 'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Codigopostal
     * ✅ Usa PK_CODIGOPOSTAL (indexado)
     */
    public function codigopostal()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Codigopostal::class, 'idcodigopostal', 'idcodigopostal');
    }

    /**
     * Relación: Poblacion
     * ⚠️  SIN ÍNDICE en IDPOBLACION
     */
    public function poblacion()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Poblacion::class, 'idpoblacion', 'idpoblacion');
    }
}
