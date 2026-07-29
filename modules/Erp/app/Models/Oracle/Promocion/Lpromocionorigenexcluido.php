<?php

namespace Modules\Erp\Models\Oracle\Promocion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Pedido\Origenpedidocli;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LPROMOCIONORIGENEXCLUIDO
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_LPROMOCIONORIGENEXCLUIDO_I (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDORIGENPEDIDOCLI
 *
 * PK_LPROMOCIONORIGENEXCLUIDO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLPROMOCIONORIGENEXCLUIDO
 *
 * ⚠️  UK_LPROMORIGEXCL_ORIG (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPROMOCION, IDORIGENPEDIDOCLI
 */
class Lpromocionorigenexcluido extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lpromocionorigenexcluido';

    protected $primaryKey = 'idlpromocionorigenexcluido';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idpromocion', 'idorigenpedidocli', 'estado', 'idusuariocre', 'idusuariomod',
        'idusuariobaj',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Origenpedidocli
     */
    public function origenpedocli()
    {
        return $this->belongsTo(Origenpedidocli::class, 'idorigenpedidocli', 'idorigenpedidocli');
    }

    /**
     * Relación con Promocion
     */
    public function promocion()
    {
        return $this->belongsTo(Promocion::class, 'idpromocion', 'idpromocion');
    }

    /**
     * Relación: Origenpedidocli
     * ✅ Usa IDX_LPROMOCIONORIGENEXCLUIDO_I (indexado)
     */
    public function origenpedidocli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\Origenpedidocli::class, 'idorigenpedidocli', 'idorigenpedidocli');
    }

    /**
     * Relación: Lpromocionorigenexcluido
     * ✅ Usa PK_LPROMOCIONORIGENEXCLUIDO (indexado)
     */
    public function lpromocionorigenexcluido()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Promocion\Lpromocionorigenexcluido::class, 'idlpromocionorigenexcluido', 'idlpromocionorigenexcluido');
    }
}
