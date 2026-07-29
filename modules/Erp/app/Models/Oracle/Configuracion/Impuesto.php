<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla IMPUESTO
 *
 * ÍNDICES DISPONIBLES:
 * PK_IMPUESTO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDIMPUESTO
 */
class Impuesto extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'impuesto';

    protected $primaryKey = 'idimpuesto';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'valoriva', 'not', 'recargo', 'not', 'idusuariomod',
        'estado', 'idscivarep', 'idscivasop', 'idscrecargoc', 'idscrecargov',
        'idscivarep_sinrec', 'idscivasop_sinrec',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Impuesto
     * ✅ Usa PK_IMPUESTO (indexado)
     */
    public function impuesto()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Impuesto::class, 'idimpuesto', 'idimpuesto');
    }
}
