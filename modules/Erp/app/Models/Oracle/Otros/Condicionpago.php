<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CONDICIONPAGO
 *
 * ÍNDICES DISPONIBLES:
 * PK_IDCONDICIONPAGO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCONDICIONPAGO
 */
class Condicionpago extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'condicionpago';

    protected $primaryKey = 'idcondicionpago';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'borrado', 'descripcion',
        'alias', 'nplazos', 'ndias', 'diasprimero',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Condicionpago
     * ✅ Usa PK_IDCONDICIONPAGO (indexado)
     */
    public function condicionpago()
    {
        return $this->belongsTo(Condicionpago::class, 'idcondicionpago', 'idcondicionpago');
    }
}
