<?php

namespace Modules\Erp\Models\Oracle\Promocion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla GENERACION_BONO_PROMOCION
 *
 * ÍNDICES DISPONIBLES:
 * PK_GENERACION_BONO_PROMOCION (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDGENERACION_BONO_PROMO
 */
class GeneracionBonoPromocion extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'generacion_bono_promocion';

    protected $primaryKey = 'idgeneracion_bono_promo';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado', 'fecha',
        'descripcion',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con LgeneracionBonoPromocion
     */
    public function lgeneracionBonoPromocions()
    {
        return $this->hasMany(LgeneracionBonoPromocion::class, 'idgeneracion_bono_promo', 'idgeneracion_bono_promocion');
    }

    /**
     * Relación: GeneracionBonoPromo
     * ✅ Usa PK_GENERACION_BONO_PROMOCION (indexado)
     */
    public function generacionBonoPromo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Promocion\GeneracionBonoPromocion::class, 'idgeneracion_bono_promo', 'idgeneracion_bono_promo');
    }
}
