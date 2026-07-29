<?php

namespace Modules\Erp\Models\Oracle\Cobro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TCOBRO
 *
 * ÍNDICES DISPONIBLES:
 * PK_IDTCOBRO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTCOBRO
 */
class Tcobro extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tcobro';

    protected $primaryKey = 'idtcobro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'borrado', 'descripcion',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Tcobro
     * ✅ Usa PK_IDTCOBRO (indexado)
     */
    public function tcobro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\Tcobro::class, 'idtcobro', 'idtcobro');
    }
}
