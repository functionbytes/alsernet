<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LCATEGORIA_CLIENTE
 *
 * ÍNDICES DISPONIBLES:
 * PK_LCATEGORIA_CLIENTE (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLCATEGORIA_CLIENTE
 */
class LcategoriaCliente extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lcategoria_cliente';

    protected $primaryKey = 'idlcategoria_cliente';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcategoria_cliente', 'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
        'flimite', 'factor_liquida_punto', 'not', 'factor_genera_punto', 'not',
    ];

    protected $casts = [
        'flimite' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: LcategoriaCliente
     * ✅ Usa PK_LCATEGORIA_CLIENTE (indexado)
     */
    public function lcategoriaCliente()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\LcategoriaCliente::class, 'idlcategoria_cliente', 'idlcategoria_cliente');
    }

    /**
     * Relación: CategoriaCliente
     * ⚠️  SIN ÍNDICE en IDCATEGORIA_CLIENTE
     */
    public function categoriaCliente()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\CategoriaCliente::class, 'idcategoria_cliente', 'idcategoria_cliente');
    }
}
