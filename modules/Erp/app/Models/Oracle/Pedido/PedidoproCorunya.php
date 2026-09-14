<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Models\Oracle\Configuracion\Almacen;
use Modules\Erp\Models\Oracle\Configuracion\Conversionmoneda;
use Modules\Erp\Models\Oracle\Configuracion\Regfiscal;
use Modules\Erp\Models\Oracle\Proveedor\Proveedor;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla PEDIDOPRO_CORUNYA
 *
 * ÍNDICES DISPONIBLES:
 * PK_PEDIDOPRO_CORUNYA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPEDIDOPRO
 */
class PedidoproCorunya extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'pedidopro_corunya';

    protected $primaryKey = 'idpedidopro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    protected $fillable = [
        'idproveedor', 'fminentrega', 'fmaxentrega', 'portes', 'estado',
        'dto', 'npedidopro', 'fpedido', 'idalmacen', 'idusuariomod',
        'idseriepedidopro', 'npedido', 'idempleado', 'idregfiscal', 'observaciones',
        'idtipopedidoprov', 'tipopedido', 'idconversionmoneda', 'estpowerpick',
    ];

    protected $casts = [
        'fminentrega' => 'datetime',
        'fmaxentrega' => 'datetime',
        'fpedido' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Pedidopro
     * ✅ Usa PK_PEDIDOPRO_CORUNYA (indexado)
     */
    public function pedidopro()
    {
        return $this->belongsTo(PedidoproCapthaya::class, 'idpedidopro', 'idpedidopro');
    }

    /**
     * Relación: Proveedor
     * ⚠️  SIN ÍNDICE en IDPROVEEDOR
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'idproveedor', 'idproveedor');
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
     * Relación: Seriepedidopro
     * ⚠️  SIN ÍNDICE en IDSERIEPEDIDOPRO
     */
    public function seriepedidopro()
    {
        return $this->belongsTo(SeriepedidoproCapthaya::class, 'idseriepedidopro', 'idseriepedidopro');
    }

    /**
     * Relación: Regfiscal
     * ⚠️  SIN ÍNDICE en IDREGFISCAL
     */
    public function regfiscal()
    {
        return $this->belongsTo(Regfiscal::class, 'idregfiscal', 'idregfiscal');
    }

    /**
     * Relación: Tipopedidoprov
     * ⚠️  SIN ÍNDICE en IDTIPOPEDIDOPROV
     */
    public function tipopedidoprov()
    {
        return $this->belongsTo(Tipopedidoproveedor::class, 'idtipopedidoprov', 'idtipopedidoprov');
    }

    /**
     * Relación: Conversionmoneda
     * ⚠️  SIN ÍNDICE en IDCONVERSIONMONEDA
     */
    public function conversionmoneda()
    {
        return $this->belongsTo(Conversionmoneda::class, 'idconversionmoneda', 'idconversionmoneda');
    }
}
