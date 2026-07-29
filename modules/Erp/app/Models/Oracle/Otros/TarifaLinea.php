<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TARIFA_LINEA
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_TARIFA_LINEA_IDTARIFA_CABE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTARIFA_CABECERA
 *
 * ✅ IDX_TARIFA_LINEA_UDESDE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: UDESDE
 *
 * PK_TARIFALINEA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTARIFA_LINEA
 */
class TarifaLinea extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tarifa_linea';

    protected $primaryKey = 'idtarifa_linea';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado', 'idtarifa_cabecera',
        'udesde', 'baseimp', 'not', 'pvp', 'not',
        'pvp_exento', 'not', 'dto', 'not', 'mostrar_dto',
        'motivo_dto', 'genera_puntos_fid', 'aplicar_ofertas',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: TarifaLinea
     * ✅ Usa PK_TARIFALINEA (indexado)
     */
    public function tarifaLinea()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\TarifaLinea::class, 'idtarifa_linea', 'idtarifa_linea');
    }

    /**
     * Relación: TarifaCabecera
     * ✅ Usa IDX_TARIFA_LINEA_IDTARIFA_CABE (indexado)
     */
    public function tarifaCabecera()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\TarifaCabecera::class, 'idtarifa_cabecera', 'idtarifa_cabecera');
    }
}
