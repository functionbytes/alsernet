<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla PEDIDOCLIESTADO (Catálogo de Estados de Pedido)
 *
 * @property int $estado Clave primaria (PK) - Código del estado
 * @property string $descripcion Descripción del estado
 *
 * ÍNDICES DISPONIBLES:
 * PK_PEDIDOCLIESTADO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ESTADO
 */
class Pedidocliestado extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'pedidocliestado';

    protected $primaryKey = 'estado';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'descripcion', 'buscamercancia', 'liberar', 'pide_a_tienda',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];
}
