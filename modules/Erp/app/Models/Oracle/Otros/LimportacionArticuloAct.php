<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Proveedor\Artiprov;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LIMPORTACION_ARTICULO_ACT
 *
 * ÍNDICES DISPONIBLES:
 * PK_LIMPORTACION_ARTICULO_ACT (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLIMPORTACION_ARTICULO_ACT
 */
class LimportacionArticuloAct extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'limportacion_articulo_act';

    protected $primaryKey = 'idlimportacion_articulo_act';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idimportacion_articulo', 'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado',
        'seleccionado', 'fprocesado', 'idartiprov', 'preciorecomendadoprov_coniva', 'pcosto',
        'dto1', 'dto2', 'actualizar_coste', 'codigopro', 'descripcionpro',
        'pvp', 'pvp_portugal', 'tarifa_calculada', 'actualizar_pvp', 'ean13',
        'upc', 'peso',
    ];

    protected $casts = [
        'fprocesado' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: LimportacionArticuloAct
     * ✅ Usa PK_LIMPORTACION_ARTICULO_ACT (indexado)
     */
    public function limportacionArticuloAct()
    {
        return $this->belongsTo(LimportacionArticuloAct::class, 'idlimportacion_articulo_act', 'idlimportacion_articulo_act');
    }

    /**
     * Relación: ImportacionArticulo
     * ⚠️  SIN ÍNDICE en IDIMPORTACION_ARTICULO
     */
    public function importacionArticulo()
    {
        return $this->belongsTo(ImportacionArticulo::class, 'idimportacion_articulo', 'idimportacion_articulo');
    }

    /**
     * Relación: Artiprov
     * ⚠️  SIN ÍNDICE en IDARTIPROV
     */
    public function artiprov()
    {
        return $this->belongsTo(Artiprov::class, 'idartiprov', 'idartiprov');
    }
}
