<?php

namespace Modules\Erp\Models\Oracle\Promocion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Cliente\Cliente;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LGENERACION_BONO_PROMOCION
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_LGENERACION_BONO_PROMO_CLI (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTE
 *
 * ✅ IDX_LGENERACION_BONO_PROMO_GEN (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDGENERACION_BONO_PROMO
 *
 * ✅ IDX_LGENERACION_BONO_PROMO_TBO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTBONO_PROMOCION
 *
 * PK_LGENERACION_BONO_PROMOCION (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLGENERACION_BONO_PROMO
 */
class LgeneracionBonoPromocion extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lgeneracion_bono_promocion';

    protected $primaryKey = 'idlgeneracion_bono_promo';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idgeneracion_bono_promo', 'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado',
        'idcliente', 'idtbono_promocion', 'observacion', 'idbono_promocion', 'generacion_fecha',
        'generacion_idusuario',
    ];

    protected $casts = [
        'generacion_fecha' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con GeneracionBonoPromocion
     */
    public function generacion_bono_promo()
    {
        return $this->belongsTo(GeneracionBonoPromocion::class, 'idgeneracion_bono_promo', 'idgeneracion_bono_promocion');
    }

    /**
     * Relación con Cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idcliente', 'idcliente_cent');
    }

    /**
     * Relación con TbonoPromocion
     */
    public function tbono_promocion()
    {
        return $this->belongsTo(TbonoPromocion::class, 'idtbono_promocion', 'idtbono_promocion');
    }

    /**
     * Relación: GeneracionBonoPromo
     * ✅ Usa IDX_LGENERACION_BONO_PROMO_GEN (indexado)
     */
    public function generacionBonoPromo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Promocion\GeneracionBonoPromocion::class, 'idgeneracion_bono_promo', 'idgeneracion_bono_promo');
    }

    /**
     * Relación: TbonoPromocion
     * ✅ Usa IDX_LGENERACION_BONO_PROMO_TBO (indexado)
     */
    public function tbonoPromocion()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Promocion\TbonoPromocion::class, 'idtbono_promocion', 'idtbono_promocion');
    }

    /**
     * Relación: LgeneracionBonoPromo
     * ✅ Usa PK_LGENERACION_BONO_PROMOCION (indexado)
     */
    public function lgeneracionBonoPromo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Promocion\LgeneracionBonoPromocion::class, 'idlgeneracion_bono_promo', 'idlgeneracion_bono_promo');
    }

    /**
     * Relación: BonoPromocion
     * ⚠️  SIN ÍNDICE en IDBONO_PROMOCION
     */
    public function bonoPromocion()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Promocion\BonoPromocion::class, 'idbono_promocion', 'idbono_promocion');
    }
}
