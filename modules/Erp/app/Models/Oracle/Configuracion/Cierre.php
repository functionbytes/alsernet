<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CIERRE_CENTRAL
 *
 * ÍNDICES DISPONIBLES:
 * PK_CIERRE_CENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCIERRE
 */
class Cierre extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'cierre_central';

    protected $primaryKey = 'idcierre';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcaja', 'estado', 'idusuariomod', 'imp_inicial', 'observaciones',
        'fcierre', 'fapertura', 'ncierre', 'idempleado', 'idcierrec',
        'idalmacen', 'idasiento',
    ];

    protected $casts = [
        'fcierre' => 'datetime',
        'fapertura' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Cierre
     * ✅ Usa PK_CIERRE_CENTRAL (indexado)
     */
    public function cierre()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Cierre::class, 'idcierre', 'idcierre');
    }

    /**
     * Relación: Almacen
     * ⚠️  SIN ÍNDICE en IDALMACEN
     */
    public function almacen()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Almacen::class, 'idalmacen', 'idalmacen');
    }

    /**
     * Relación: Asiento
     * ⚠️  SIN ÍNDICE en IDASIENTO
     */
    public function asiento()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\AsientoCent::class, 'idasiento', 'idasiento');
    }
}
