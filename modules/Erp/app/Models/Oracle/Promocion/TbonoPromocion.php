<?php

namespace Modules\Erp\Models\Oracle\Promocion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Configuracion\Seguro;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TBONO_PROMOCION
 *
 * ÍNDICES DISPONIBLES:
 * PK_TBONO_PROMOCION (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTBONO_PROMOCION
 *
 * ⚠️  UK_TBONO_PROMOCION_TIPO_ (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: TIPO
 */
class TbonoPromocion extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tbono_promocion';

    protected $primaryKey = 'idtbono_promocion';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'tipo', 'descripcion', 'idusuariocrea', 'idusuariomod', 'idusuariobaj',
        'estado', 'fvalidez_desde', 'fvalidez_hasta', 'no_antes_n_dias', 'dias',
        'importe', 'importeminimoventa',
    ];

    protected $casts = [
        'fvalidez_desde' => 'datetime',
        'fvalidez_hasta' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con BonoPromocion
     */
    public function bonoPromocions()
    {
        return $this->hasMany(BonoPromocion::class, 'idtbono_promocion', 'idtbono_promocion');
    }

    /**
     * Relación inversa con LgeneracionBonoPromocion
     */
    public function lgeneracionBonoPromocions()
    {
        return $this->hasMany(LgeneracionBonoPromocion::class, 'idtbono_promocion', 'idtbono_promocion');
    }

    /**
     * Relación inversa con Seguro
     */
    public function seguros()
    {
        return $this->hasMany(Seguro::class, 'idtbono_promocion', 'idtbono_promocion');
    }

    /**
     * Relación: TbonoPromocion
     * ✅ Usa PK_TBONO_PROMOCION (indexado)
     */
    public function tbonoPromocion()
    {
        return $this->belongsTo(TbonoPromocion::class, 'idtbono_promocion', 'idtbono_promocion');
    }
}
