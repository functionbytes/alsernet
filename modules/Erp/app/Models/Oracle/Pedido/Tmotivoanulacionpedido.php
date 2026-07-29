<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TMOTIVOANULACIONPEDIDO
 *
 * ÍNDICES DISPONIBLES:
 * PK_IDTMOTIVOANULACIONPEDIDO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTMOTIVOANULACIONPEDIDO
 */
class Tmotivoanulacionpedido extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tmotivoanulacionpedido';

    protected $primaryKey = 'idtmotivoanulacionpedido';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaja', 'estado', 'descripcion',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Tmotivoanulacionpedido
     * ✅ Usa PK_IDTMOTIVOANULACIONPEDIDO (indexado)
     */
    public function tmotivoanulacionpedido()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\Tmotivoanulacionpedido::class, 'idtmotivoanulacionpedido', 'idtmotivoanulacionpedido');
    }
}
