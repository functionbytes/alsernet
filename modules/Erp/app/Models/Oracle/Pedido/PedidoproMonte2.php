<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla PEDIDOPRO_MONTE2
 *
 * ÍNDICES DISPONIBLES:
 * PK_PEDIDOPRO_MONTE2 (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPEDIDOPRO
 */
class PedidoproMonte2 extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'pedidopro_monte2';

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
     * ✅ Usa PK_PEDIDOPRO_MONTE2 (indexado)
     */
    public function pedidopro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\PedidoproCapthaya::class, 'idpedidopro', 'idpedidopro');
    }

    /**
     * Relación: Proveedor
     * ⚠️  SIN ÍNDICE en IDPROVEEDOR
     */
    public function proveedor()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Proveedor\Proveedor::class, 'idproveedor', 'idproveedor');
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
     * Relación: Seriepedidopro
     * ⚠️  SIN ÍNDICE en IDSERIEPEDIDOPRO
     */
    public function seriepedidopro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\SeriepedidoproCapthaya::class, 'idseriepedidopro', 'idseriepedidopro');
    }

    /**
     * Relación: Regfiscal
     * ⚠️  SIN ÍNDICE en IDREGFISCAL
     */
    public function regfiscal()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Regfiscal::class, 'idregfiscal', 'idregfiscal');
    }

    /**
     * Relación: Tipopedidoprov
     * ⚠️  SIN ÍNDICE en IDTIPOPEDIDOPROV
     */
    public function tipopedidoprov()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\Tipopedidoproveedor::class, 'idtipopedidoprov', 'idtipopedidoprov');
    }

    /**
     * Relación: Conversionmoneda
     * ⚠️  SIN ÍNDICE en IDCONVERSIONMONEDA
     */
    public function conversionmoneda()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Conversionmoneda::class, 'idconversionmoneda', 'idconversionmoneda');
    }
}
