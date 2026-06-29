<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla EVENTO
 *
 * ÍNDICES DISPONIBLES:
 * PK_IDEVENTO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDEVENTO
 */
class Evento extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'evento';

    protected $primaryKey = 'idevento';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'borrado', 'descripcion',
        'habilitado',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Evento
     * ✅ Usa PK_IDEVENTO (indexado)
     */
    public function evento()
    {
        return $this->belongsTo(Evento::class, 'idevento', 'idevento');
    }
}
