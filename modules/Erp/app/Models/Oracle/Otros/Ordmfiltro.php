<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ORDMFILTRO
 *
 * ÍNDICES DISPONIBLES:
 * PK_ORDMFILTRO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDORDMFILTRO
 */
class Ordmfiltro extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'ordmfiltro';

    protected $primaryKey = 'idordmfiltro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcampo', 'idmfiltro', 'estado', 'idusuariomod', 'nombre',
        'descripcion', 'visible',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Ordmfiltro
     * ✅ Usa PK_ORDMFILTRO (indexado)
     */
    public function ordmfiltro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Ordmfiltro::class, 'idordmfiltro', 'idordmfiltro');
    }

    /**
     * Relación: Campo
     * ⚠️  SIN ÍNDICE en IDCAMPO
     */
    public function campo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Campo::class, 'idcampo', 'idcampo');
    }

    /**
     * Relación: Mfiltro
     * ⚠️  SIN ÍNDICE en IDMFILTRO
     */
    public function mfiltro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Mfiltro::class, 'idmfiltro', 'idmfiltro');
    }
}
