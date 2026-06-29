<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TARJETAS
 *
 * ÍNDICES DISPONIBLES:
 * PK_TARJETAS (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTARJETA
 */
class Tarjetas extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tarjetas';

    protected $primaryKey = 'idtarjeta';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'alias', 'idusuariocre', 'idusuariomod', 'idusuariobaja',
        'estado', 'estarjetacredito',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Tarjeta
     * ✅ Usa PK_TARJETAS (indexado)
     */
    public function tarjeta()
    {
        return $this->belongsTo(Tarjetas::class, 'idtarjeta', 'idtarjeta');
    }
}
