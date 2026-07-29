<?php

namespace Modules\Erp\Models\Oracle\Promocion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LREBAJA_X_ALMACEN
 *
 * ÍNDICES DISPONIBLES:
 * PK_LREBAJA_X_ALM (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLREBAJA_X_ALMACEN
 */
class LrebajaXAlmacen extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lrebaja_x_almacen';

    protected $primaryKey = 'idlrebaja_x_almacen';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idrebaja', 'idalmacen', 'estado', 'idusuariocre', 'idusuariomod',
        'idusuariobaj',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: LrebajaXAlmacen
     * ✅ Usa PK_LREBAJA_X_ALM (indexado)
     */
    public function lrebajaXAlmacen()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Promocion\LrebajaXAlmacen::class, 'idlrebaja_x_almacen', 'idlrebaja_x_almacen');
    }

    /**
     * Relación: Rebaja
     * ⚠️  SIN ÍNDICE en IDREBAJA
     */
    public function rebaja()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Promocion\Rebaja::class, 'idrebaja', 'idrebaja');
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
