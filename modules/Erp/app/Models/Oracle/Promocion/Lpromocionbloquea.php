<?php

namespace Modules\Erp\Models\Oracle\Promocion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LPROMOCIONBLOQUEA
 *
 * ÍNDICES DISPONIBLES:
 * PK_LPROMOCIONBLOQUEA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLPROMOCIONBLOQUEA
 */
class Lpromocionbloquea extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lpromocionbloquea';

    protected $primaryKey = 'idlpromocionbloquea';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idpromocion', 'idarticulo', 'estado', 'idusuariocre', 'idusuariomod',
        'idusuariobaj',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Lpromocionbloquea
     * ✅ Usa PK_LPROMOCIONBLOQUEA (indexado)
     */
    public function lpromocionbloquea()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Promocion\Lpromocionbloquea::class, 'idlpromocionbloquea', 'idlpromocionbloquea');
    }

    /**
     * Relación: Promocion
     * ⚠️  SIN ÍNDICE en IDPROMOCION
     */
    public function promocion()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Promocion\Promocion::class, 'idpromocion', 'idpromocion');
    }

    /**
     * Relación: Articulo
     * ⚠️  SIN ÍNDICE en IDARTICULO
     */
    public function articulo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Articulo\Articulo::class, 'idarticulo', 'idarticulo');
    }
}
