<?php

namespace Modules\Erp\Models\Oracle\Factura;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Models\Oracle\Albaran\LalbarancliCapthaya;
use Modules\Erp\Models\Oracle\Albaran\LalbarancliCentral;
use Modules\Erp\Models\Oracle\Articulo\Articulo;
use Modules\Erp\Models\Oracle\Configuracion\Almacen;
use Modules\Erp\Models\Oracle\Configuracion\Tipomedida;
use Modules\Erp\Models\Oracle\Lote\Lote;
use Modules\Erp\Models\Oracle\Pedido\LpedidocliCapthaya;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LFACTURACLI_CENTRAL
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_FACTURACLICENT_IDLALBCENT (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLALBARANCLI_CENTRAL
 *
 * ✅ INDX_LFACTURACLI_IDFACLI (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFACTURACLI
 *
 * PK_LFACTURACLICENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLFACTURACLI
 */
class LfacturacliCentral extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lfacturacli_central';

    protected $primaryKey = 'idlfacturacli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    protected $fillable = [
        'idfacturacli', 'idlalbarancli', 'idarticulo', 'unidades', 'not',
        'iva', 'not', 'recargo', 'not', 'pbi',
        'not', 'dto', 'not', 'idusuariomod', 'codigo',
        'descripcion', 'dtocabecera', 'idtipomedida', 'unid', 'idlpedidocli',
        'idlote', 'seclote', 'total_bi', 'total_con_impuestos', 'idalmacen',
        'idlalbarancli_central', 'ngrupo_segundamano', 'parte_exenta', 'not', 'nexpediente',
        'fexpediente', 'numero_serie',
    ];

    protected $casts = [
        'fexpediente' => 'datetime',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Lfacturacli
     * ✅ Usa PK_LFACTURACLICENTRAL (indexado)
     */
    public function lfacturacli()
    {
        return $this->belongsTo(LfacturacliCentral::class, 'idlfacturacli', 'idlfacturacli');
    }

    /**
     * Relación: Facturacli
     * ✅ Usa INDX_LFACTURACLI_IDFACLI (indexado)
     */
    public function facturacli()
    {
        return $this->belongsTo(FacturacliCentral::class, 'idfacturacli', 'idfacturacli');
    }

    /**
     * Relación: Lalbarancli
     * ⚠️  SIN ÍNDICE en IDLALBARANCLI
     */
    public function lalbarancli()
    {
        return $this->belongsTo(LalbarancliCapthaya::class, 'idlalbarancli', 'idlalbarancli');
    }

    /**
     * Relación: Articulo
     * ⚠️  SIN ÍNDICE en IDARTICULO
     */
    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Relación: Tipomedida
     * ⚠️  SIN ÍNDICE en IDTIPOMEDIDA
     */
    public function tipomedida()
    {
        return $this->belongsTo(Tipomedida::class, 'idtipomedida', 'idtipomedida');
    }

    /**
     * Relación: Lpedidocli
     * ⚠️  SIN ÍNDICE en IDLPEDIDOCLI
     */
    public function lpedidocli()
    {
        return $this->belongsTo(LpedidocliCapthaya::class, 'idlpedidocli', 'idlpedidocli');
    }

    /**
     * Relación: Lote
     * ⚠️  SIN ÍNDICE en IDLOTE
     */
    public function lote()
    {
        return $this->belongsTo(Lote::class, 'idlote', 'idlote');
    }

    /**
     * Relación: Almacen
     * ⚠️  SIN ÍNDICE en IDALMACEN
     */
    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'idalmacen', 'idalmacen');
    }

    /**
     * Relación: LalbarancliCentral
     * ✅ Usa IDX_FACTURACLICENT_IDLALBCENT (indexado)
     */
    public function lalbarancliCentral()
    {
        return $this->belongsTo(LalbarancliCentral::class, 'idlalbarancli_central', 'idlalbarancli_central');
    }
}
