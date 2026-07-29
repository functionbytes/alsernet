<?php

namespace Modules\Erp\Models\Oracle\Albaran;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LALBARANCLI_CAPTHAYA
 *
 * ÍNDICES DISPONIBLES:
 * PK_LALBARANCLI_CAPTHAYA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLALBARANCLI
 */
class LalbarancliCapthaya extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lalbarancli_capthaya';

    protected $primaryKey = 'idlalbarancli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idarticulo', 'idmovalm', 'idalbarancli', 'idusuariomod', 'pcosto',
        'precio', 'not', 'unidades', 'not', 'dto',
        'not', 'iva', 'not', 'recargo', 'not',
        'precioorigen', 'idoferta', 'idalmacen', 'idtipomedida', 'observaciones',
        'unid', 'idlote', 'seclote', 'idlpedidocli', 'notapieza',
        'notageneral', 'idlalbarancliorig', 'idtipodescuento', 'total_bi', 'total_con_impuestos',
        'origen_kardex', 'idbono_promocion', 'guiapertenencia', 'fguiapertenencia', 'narma',
        'ngrupo_segundamano', 'total_neto', 'numero_serie', 'numticket', 'genera_puntos',
        'parte_exenta', 'not', 'tarifa_genera_puntos', 'idempleado_gfitters',
    ];

    protected $casts = [
        'fguiapertenencia' => 'datetime',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Lalbarancli
     * ✅ Usa PK_LALBARANCLI_CAPTHAYA (indexado)
     */
    public function lalbarancli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\LalbarancliCapthaya::class, 'idlalbarancli', 'idlalbarancli');
    }

    /**
     * Relación: Articulo
     * ⚠️  SIN ÍNDICE en IDARTICULO
     */
    public function articulo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Articulo\Articulo::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Relación: Albarancli
     * ⚠️  SIN ÍNDICE en IDALBARANCLI
     */
    public function albarancli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\AlbarancliCapthaya::class, 'idalbarancli', 'idalbarancli');
    }

    /**
     * Relación: Almacen
     * ⚠️  SIN ÍNDICE en IDALMACEN
     */
    public function almacen()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Almacen::class, 'idalmacen', 'idalmacen');
    }

    /**
     * Relación: Tipomedida
     * ⚠️  SIN ÍNDICE en IDTIPOMEDIDA
     */
    public function tipomedida()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Tipomedida::class, 'idtipomedida', 'idtipomedida');
    }

    /**
     * Relación: Lote
     * ⚠️  SIN ÍNDICE en IDLOTE
     */
    public function lote()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Lote\Lote::class, 'idlote', 'idlote');
    }

    /**
     * Relación: Lpedidocli
     * ⚠️  SIN ÍNDICE en IDLPEDIDOCLI
     */
    public function lpedidocli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\LpedidocliCapthaya::class, 'idlpedidocli', 'idlpedidocli');
    }

    /**
     * Relación: Tipodescuento
     * ⚠️  SIN ÍNDICE en IDTIPODESCUENTO
     */
    public function tipodescuento()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Promocion\Tipodescuento::class, 'idtipodescuento', 'idtipodescuento');
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
