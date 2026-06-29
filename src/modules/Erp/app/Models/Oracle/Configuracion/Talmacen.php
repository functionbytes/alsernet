<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TALMACEN
 *
 * ÍNDICES DISPONIBLES:
 * PK_TALMACEN (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTALMACEN
 */
class Talmacen extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'talmacen';

    protected $primaryKey = 'idtalmacen';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'idusuariomod', 'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Talmacen
     * ✅ Usa PK_TALMACEN (indexado)
     */
    public function talmacen()
    {
        return $this->belongsTo(Talmacen::class, 'idtalmacen', 'idtalmacen');
    }
}
