<?php

namespace Modules\Erp\Models\Oracle\Cliente;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Configuracion\Seguro;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CLIENTE_SEGURO
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_SEGURO_CLIENTE__IDBONO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDBONO
 *
 * ✅ IDX_SEGURO_CLIENTE__IDCLIENTE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTE
 *
 * ✅ IDX_SEGURO_CLIENTE__IDSEGURO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSEGURO
 *
 * PK_CLIENTE_SEGURO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTE_SEGURO
 */
class ClienteSeguro extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'cliente_seguro';

    protected $primaryKey = 'idcliente_seguro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcliente', 'idseguro', 'fecha_contratacion', 'fecha_denegacion', 'idbono',
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'observaciones', 'numero_poliza',
    ];

    protected $casts = [
        'fecha_contratacion' => 'datetime',
        'fecha_denegacion' => 'datetime',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idcliente', 'idcliente_cent');
    }

    /**
     * Relación con Seguro
     */
    public function seguro()
    {
        return $this->belongsTo(Seguro::class, 'idseguro', 'idseguro');
    }

    /**
     * Relación: ClienteSeguro
     * ✅ Usa PK_CLIENTE_SEGURO (indexado)
     */
    public function clienteSeguro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cliente\ClienteSeguro::class, 'idcliente_seguro', 'idcliente_seguro');
    }
}
