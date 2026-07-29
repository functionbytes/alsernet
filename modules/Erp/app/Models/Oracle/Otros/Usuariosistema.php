<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla USUARIOSISTEMA
 *
 * ÍNDICES DISPONIBLES:
 * PK_USUARIOSISTEMA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDUSUARIOSISTEMA
 */
class Usuariosistema extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'usuariosistema';

    protected $primaryKey = 'idusuariosistema';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'falta', 'estado', 'idusuariomod', 'login', 'password',
        'nivel', 'idempleado', 'controlriesgo', 'intentos', 'nbloqueos',
        'email', 'nombre',
    ];

    protected $casts = [
        'falta' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Usuariosistema
     * ✅ Usa PK_USUARIOSISTEMA (indexado)
     */
    public function usuariosistema()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Usuariosistema::class, 'idusuariosistema', 'idusuariosistema');
    }
}
