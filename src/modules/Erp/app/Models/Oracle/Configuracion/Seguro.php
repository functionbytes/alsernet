<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Cliente\ClienteSeguro;
use Modules\Erp\Models\Oracle\Promocion\TbonoPromocion;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla SEGURO
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_SEGURO_TBONO_PROMOCION (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTBONO_PROMOCION
 *
 * PK_SEGURO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSEGURO
 */
class Seguro extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'seguro';

    protected $primaryKey = 'idseguro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
        'bono_generar', 'idtbono_promocion',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con TbonoPromocion
     */
    public function tbono_promocion()
    {
        return $this->belongsTo(TbonoPromocion::class, 'idtbono_promocion', 'idtbono_promocion');
    }

    /**
     * Relación inversa con ClienteSeguro
     */
    public function clienteSeguros()
    {
        return $this->hasMany(ClienteSeguro::class, 'idseguro', 'idseguro');
    }

    /**
     * Relación: TbonoPromocion
     * ✅ Usa IDX_SEGURO_TBONO_PROMOCION (indexado)
     */
    public function tbonoPromocion()
    {
        return $this->belongsTo(TbonoPromocion::class, 'idtbono_promocion', 'idtbono_promocion');
    }

    /**
     * Relación: Seguro
     * ✅ Usa PK_SEGURO (indexado)
     */
    public function seguro()
    {
        return $this->belongsTo(Seguro::class, 'idseguro', 'idseguro');
    }
}
