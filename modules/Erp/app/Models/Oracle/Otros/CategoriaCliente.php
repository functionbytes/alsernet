<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Cliente\Cliente;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CATEGORIA_CLIENTE
 *
 * ÍNDICES DISPONIBLES:
 * PK_CATEGORIA_CLIENTE (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCATEGORIA_CLIENTE
 */
class CategoriaCliente extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'categoria_cliente';

    protected $primaryKey = 'idcategoria_cliente';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: CategoriaCliente
     * ✅ Usa PK_CATEGORIA_CLIENTE (indexado)
     */
    public function categoriaCliente()
    {
        return $this->belongsTo(CategoriaCliente::class, 'idcategoria_cliente', 'idcategoria_cliente');
    }

    /**
     * Clientes de esta categoría
     */
    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'idcategoria_cliente', 'idcategoria_cliente');
    }

    /**
     * Líneas de categoría
     */
    public function lineas()
    {
        return $this->hasMany(LcategoriaCliente::class, 'idcategoria_cliente', 'idcategoria_cliente');
    }
}
