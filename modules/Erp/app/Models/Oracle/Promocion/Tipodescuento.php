<?php

namespace Modules\Erp\Models\Oracle\Promocion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TIPODESCUENTO
 *
 * ÍNDICES DISPONIBLES:
 * PK_TIPODESCUENTO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTIPODESCUENTO
 */
class Tipodescuento extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tipodescuento';

    protected $primaryKey = 'idtipodescuento';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'idusuariocre', 'idusuariomod', 'idusuariobaja', 'estado',
        'puntos_fidelizacion',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Tipodescuento
     * ✅ Usa PK_TIPODESCUENTO (indexado)
     */
    public function tipodescuento()
    {
        return $this->belongsTo(Tipodescuento::class, 'idtipodescuento', 'idtipodescuento');
    }
}
