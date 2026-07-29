<?php

namespace Modules\Erp\Models\Oracle\Promocion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Configuracion\Regpais;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LREBAJA
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_LREBAJA_IDREGPAIS (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDREGPAIS
 *
 * PK_LREBAJA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLREBAJA
 *
 * ⚠️  UK_LREBAJA_ART_REGP_REB (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDREBAJA, IDARTICULO, IDREGPAIS
 */
class Lrebaja extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lrebaja';

    protected $primaryKey = 'idlrebaja';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idrebaja', 'tipo', 'porcentaje', 'precio', 'idarticulo',
        'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaj', 'precio_sin_redondeo',
        'idregpais',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Regpais
     */
    public function regpais()
    {
        return $this->belongsTo(Regpais::class, 'idregpais', 'idregpais');
    }

    /**
     * Relación: Lrebaja
     * ✅ Usa PK_LREBAJA (indexado)
     */
    public function lrebaja()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Promocion\Lrebaja::class, 'idlrebaja', 'idlrebaja');
    }

    /**
     * Relación: Rebaja
     * ✅ Usa UK_LREBAJA_ART_REGP_REB (indexado)
     */
    public function rebaja()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Promocion\Rebaja::class, 'idrebaja', 'idrebaja');
    }

    /**
     * Relación: Articulo
     * ⚠️  SIN ÍNDICE en IDARTICULO
     */
    public function articulo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Articulo\Articulo::class, 'idarticulo', 'idarticulo');
    }
}
