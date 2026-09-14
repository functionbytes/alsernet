<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla GRUPOUSUARIO_MENSAJERIA
 *
 * ÍNDICES DISPONIBLES:
 * PK_IDGRUPOUSUARIO_MENSAJERIA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDGRUPOUSUARIO_MENSAJERIA
 */
class GrupousuarioMensajeria extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'grupousuario_mensajeria';

    protected $primaryKey = 'idgrupousuario_mensajeria';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'borrado', 'idgrupo_mensajeria',
        'idusuariosistema',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: GrupousuarioMensajeria
     * ✅ Usa PK_IDGRUPOUSUARIO_MENSAJERIA (indexado)
     */
    public function grupousuarioMensajeria()
    {
        return $this->belongsTo(GrupousuarioMensajeria::class, 'idgrupousuario_mensajeria', 'idgrupousuario_mensajeria');
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
