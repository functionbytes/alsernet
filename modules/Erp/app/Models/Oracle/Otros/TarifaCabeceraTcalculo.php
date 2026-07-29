<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TARIFA_CABECERA_TCALCULO
 *
 * ÍNDICES DISPONIBLES:
 * PK_TARIFA_CABECERA_TCALCULO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTARIFA_CABECERA_TCALCULO
 */
class TarifaCabeceraTcalculo extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tarifa_cabecera_tcalculo';

    protected $primaryKey = 'idtarifa_cabecera_tcalculo';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado', 'descripcion',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: TarifaCabeceraTcalculo
     * ✅ Usa PK_TARIFA_CABECERA_TCALCULO (indexado)
     */
    public function tarifaCabeceraTcalculo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\TarifaCabeceraTcalculo::class, 'idtarifa_cabecera_tcalculo', 'idtarifa_cabecera_tcalculo');
    }
}
