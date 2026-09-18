<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Promocion\Lpromocionorigenexcluido;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ORIGENPEDIDOCLI
 *
 * ÍNDICES DISPONIBLES:
 * PK_ORIGENPEDIDOCLI (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDORIGENPEDIDOCLI
 */
class Origenpedidocli extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'origenpedidocli';

    protected $primaryKey = 'idorigenpedidocli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
        'enviar_confirmacion_pedido',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con Lpromocionorigenexcluido
     */
    public function lpromocionorigenexcluidos()
    {
        return $this->hasMany(Lpromocionorigenexcluido::class, 'idorigenpedidocli', 'idorigenpedidocli');
    }

    /**
     * Relación: Origenpedidocli
     * ✅ Usa PK_ORIGENPEDIDOCLI (indexado)
     */
    public function origenpedidocli()
    {
        return $this->belongsTo(Origenpedidocli::class, 'idorigenpedidocli', 'idorigenpedidocli');
    }
}
