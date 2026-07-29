<?php

namespace Modules\Erp\Models\Oracle\Proveedor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla PROPUESTAPRO
 *
 * ÍNDICES DISPONIBLES:
 * PK_PROPUESTAPRO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPROPUESTAPRO
 */
class Propuestapro extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'propuestapro';

    protected $primaryKey = 'idpropuestapro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idproveedor', 'idalmacen', 'idempleado', 'fpropuesta', 'estado',
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'observaciones',
    ];

    protected $casts = [
        'fpropuesta' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Propuestapro
     * ✅ Usa PK_PROPUESTAPRO (indexado)
     */
    public function propuestapro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Proveedor\Propuestapro::class, 'idpropuestapro', 'idpropuestapro');
    }

    /**
     * Relación: Proveedor
     * ⚠️  SIN ÍNDICE en IDPROVEEDOR
     */
    public function proveedor()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Proveedor\Proveedor::class, 'idproveedor', 'idproveedor');
    }

    /**
     * Relación: Almacen
     * ⚠️  SIN ÍNDICE en IDALMACEN
     */
    public function almacen()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Almacen::class, 'idalmacen', 'idalmacen');
    }
}
