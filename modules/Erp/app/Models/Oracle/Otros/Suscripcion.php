<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla SUSCRIPCION
 *
 * ÍNDICES DISPONIBLES:
 * PK_IDSUSCRIPCION (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSUSCRIPCION
 */
class Suscripcion extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'suscripcion';

    protected $primaryKey = 'idsuscripcion';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'borrado', 'idevento',
        'idgrupo_mensajeria', 'idusuariosistema',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Suscripcion
     * ✅ Usa PK_IDSUSCRIPCION (indexado)
     */
    public function suscripcion()
    {
        return $this->belongsTo(Suscripcion::class, 'idsuscripcion', 'idsuscripcion');
    }

    /**
     * Relación: Evento
     * ⚠️  SIN ÍNDICE en IDEVENTO
     */
    public function evento()
    {
        return $this->belongsTo(Evento::class, 'idevento', 'idevento');
    }

    /**
     * Relación: Usuariosistema
     * ⚠️  SIN ÍNDICE en IDUSUARIOSISTEMA
     */
    public function usuariosistema()
    {
        return $this->belongsTo(Usuariosistema::class, 'idusuariosistema', 'idusuariosistema');
    }
}
