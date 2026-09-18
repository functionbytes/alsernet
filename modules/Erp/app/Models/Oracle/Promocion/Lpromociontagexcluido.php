<?php

namespace Modules\Erp\Models\Oracle\Promocion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LPROMOCIONTAGEXCLUIDO
 *
 * ÍNDICES DISPONIBLES:
 * PK_LPROMOCIONTAGEXCLUIDO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLPROMOCIONTAGEXCLUIDO
 */
class Lpromociontagexcluido extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lpromociontagexcluido';

    protected $primaryKey = 'idlpromociontagexcluido';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idpromocion', 'tagarticulo', 'estado', 'idusuariocre', 'idusuariomod',
        'idusuariobaj',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Lpromociontagexcluido
     * ✅ Usa PK_LPROMOCIONTAGEXCLUIDO (indexado)
     */
    public function lpromociontagexcluido()
    {
        return $this->belongsTo(Lpromociontagexcluido::class, 'idlpromociontagexcluido', 'idlpromociontagexcluido');
    }

    /**
     * Relación: Promocion
     * ⚠️  SIN ÍNDICE en IDPROMOCION
     */
    public function promocion()
    {
        return $this->belongsTo(Promocion::class, 'idpromocion', 'idpromocion');
    }
}
