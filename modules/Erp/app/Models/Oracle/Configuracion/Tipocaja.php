<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TIPOCAJA
 *
 * ÍNDICES DISPONIBLES:
 * PK_TIPOCAJA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTIPOCAJA
 */
class Tipocaja extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tipocaja';

    protected $primaryKey = 'idtipocaja';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'estado', 'idusuariomod', 'descripcion',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Tipocaja
     * ✅ Usa PK_TIPOCAJA (indexado)
     */
    public function tipocaja()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Tipocaja::class, 'idtipocaja', 'idtipocaja');
    }
}
