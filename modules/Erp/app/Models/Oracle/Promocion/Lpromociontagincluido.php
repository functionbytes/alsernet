<?php

namespace Modules\Erp\Models\Oracle\Promocion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LPROMOCIONTAGINCLUIDO
 *
 * ÍNDICES DISPONIBLES:
 * PK_LPROMOCIONTAGINCLUIDO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLPROMOCIONTAGINCLUIDO
 *
 * ⚠️  UK_LPROMTAGINCL_TAG_PROMO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPROMOCION, TAGARTICULO
 */
class Lpromociontagincluido extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lpromociontagincluido';

    protected $primaryKey = 'idlpromociontagincluido';

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
     * Relación con Promocion
     */
    public function promocion()
    {
        return $this->belongsTo(Promocion::class, 'idpromocion', 'idpromocion');
    }

    /**
     * Relación: Lpromociontagincluido
     * ✅ Usa PK_LPROMOCIONTAGINCLUIDO (indexado)
     */
    public function lpromociontagincluido()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Promocion\Lpromociontagincluido::class, 'idlpromociontagincluido', 'idlpromociontagincluido');
    }
}
