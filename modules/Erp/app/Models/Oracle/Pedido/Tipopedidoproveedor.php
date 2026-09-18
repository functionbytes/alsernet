<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TIPOPEDIDOPROVEEDOR
 *
 * ÍNDICES DISPONIBLES:
 * PK_TIPOPEDIDOPROVEEDOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTIPOPEDIDOPROV
 */
class Tipopedidoproveedor extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tipopedidoproveedor';

    protected $primaryKey = 'idtipopedidoprov';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Tipopedidoprov
     * ✅ Usa PK_TIPOPEDIDOPROVEEDOR (indexado)
     */
    public function tipopedidoprov()
    {
        return $this->belongsTo(Tipopedidoproveedor::class, 'idtipopedidoprov', 'idtipopedidoprov');
    }
}
