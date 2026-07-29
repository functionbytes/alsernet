<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TARIFA_CABECERA
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_TARIFA_CABECERA_FFIN (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: FFIN
 *
 * ✅ IDX_TARIFA_CABECERA_FINICIO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: FINICIO
 *
 * ✅ IDX_TARIFA_CABECERA_IDALMACEN (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALMACEN
 *
 * ✅ IDX_TARIFA_CABECERA_IDARTICULO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTICULO
 *
 * ✅ IDX_TARIFA_CABECERA_IDIMPPAIS_ (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDIMPPAIS_FECHA
 *
 * ✅ IDX_TARIFA_CABECERA_IDREGPAIS (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDREGPAIS
 *
 * ✅ IDX_TARIFA_CABECERA_IDTARIFA_C (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTARIFA_CABECERA_TCALCULO
 *
 * PK_TARIFACABECERA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTARIFA_CABECERA
 */
class TarifaCabecera extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tarifa_cabecera';

    protected $primaryKey = 'idtarifa_cabecera_tcalculo';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idtarifa_cabecera', 'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado',
        'idarticulo', 'idalmacen', 'idregpais', 'idimppais_fecha', 'porc_iva',
        'not', 'tarifa_base', 'tarifa_calculada', 'importe_exento', 'not',
        'finicio', 'ffin', 'idttarifa',
    ];

    protected $casts = [
        'finicio' => 'datetime',
        'ffin' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: TarifaCabecera
     * ✅ Usa PK_TARIFACABECERA (indexado)
     */
    public function tarifaCabecera()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\TarifaCabecera::class, 'idtarifa_cabecera', 'idtarifa_cabecera');
    }

    /**
     * Relación: Articulo
     * ✅ Usa IDX_TARIFA_CABECERA_IDARTICULO (indexado)
     */
    public function articulo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Articulo\Articulo::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Relación: Almacen
     * ✅ Usa IDX_TARIFA_CABECERA_IDALMACEN (indexado)
     */
    public function almacen()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Almacen::class, 'idalmacen', 'idalmacen');
    }

    /**
     * Relación: Regpais
     * ✅ Usa IDX_TARIFA_CABECERA_IDREGPAIS (indexado)
     */
    public function regpais()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Regpais::class, 'idregpais', 'idregpais');
    }

    /**
     * Relación: ImppaisFecha
     * ✅ Usa IDX_TARIFA_CABECERA_IDIMPPAIS_ (indexado)
     */
    public function imppaisFecha()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\ImppaisFecha::class, 'idimppais_fecha', 'idimppais_fecha');
    }

    /**
     * Relación: TarifaCabeceraTcalculo
     * ✅ Usa IDX_TARIFA_CABECERA_IDTARIFA_C (indexado)
     */
    public function tarifaCabeceraTcalculo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\TarifaCabeceraTcalculo::class, 'idtarifa_cabecera_tcalculo', 'idtarifa_cabecera_tcalculo');
    }

    /**
     * Relación: Ttarifa
     * ⚠️  SIN ÍNDICE en IDTTARIFA
     */
    public function ttarifa()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Ttarifa::class, 'idttarifa', 'idttarifa');
    }

    /**
     * Líneas de tarifa asociadas
     * ✅ Usa IDX_TARIFA_LINEA_IDTARIFA_CABE (indexado)
     */
    public function tarifasLinea()
    {
        return $this->hasMany(\Modules\Erp\Models\Oracle\Otros\TarifaLinea::class, 'idtarifa_cabecera', 'idtarifa_cabecera');
    }
}
