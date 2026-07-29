<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla OBSERVADOR
 *
 * ÍNDICES DISPONIBLES:
 * PK_IDOBSERVADOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDOBSERVADOR
 */
class Observador extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'observador';

    protected $primaryKey = 'idobservador';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'borrado', 'identificador',
        'tipo', 'idusuariosistema', 'participante',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Observador
     * ✅ Usa PK_IDOBSERVADOR (indexado)
     */
    public function observador()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Observador::class, 'idobservador', 'idobservador');
    }

    /**
     * Relación: Usuariosistema
     * ⚠️  SIN ÍNDICE en IDUSUARIOSISTEMA
     */
    public function usuariosistema()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Usuariosistema::class, 'idusuariosistema', 'idusuariosistema');
    }
}
