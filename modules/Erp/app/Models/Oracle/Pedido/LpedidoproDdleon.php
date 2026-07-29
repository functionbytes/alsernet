<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LPEDIDOPRO_DDLEON
 *
 * ÍNDICES DISPONIBLES:
 * PK_LPEDIDOPRO_DDLEON (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLPEDIDOPRO
 */
class LpedidoproDdleon extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lpedidopro_ddleon';

    protected $primaryKey = 'idlpedidopro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    protected $fillable = [
        'idpedidopro', 'idarticulo', 'fminentrega', 'fmaxentrega', 'unidades',
        'not', 'precio', 'dto', 'tipo', 'idusuariomod',
        'idtipomedida', 'unid', 'iva', 'recargo', 'idlpedidocli',
        'notapieza', 'dto2', 'preciomonedaoriginal', 'idlpropuestapro', 'unidades_recomendadas',
        'unidades_originales',
    ];

    protected $casts = [
        'fminentrega' => 'datetime',
        'fmaxentrega' => 'datetime',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Lpedidopro
     * ✅ Usa PK_LPEDIDOPRO_DDLEON (indexado)
     */
    public function lpedidopro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\LpedidoproCapthaya::class, 'idlpedidopro', 'idlpedidopro');
    }

    /**
     * Relación: Pedidopro
     * ⚠️  SIN ÍNDICE en IDPEDIDOPRO
     */
    public function pedidopro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\PedidoproCapthaya::class, 'idpedidopro', 'idpedidopro');
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
     * Relación: Tipomedida
     * ⚠️  SIN ÍNDICE en IDTIPOMEDIDA
     */
    public function tipomedida()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Tipomedida::class, 'idtipomedida', 'idtipomedida');
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
     * Relación: Lpropuestapro
     * ⚠️  SIN ÍNDICE en IDLPROPUESTAPRO
     */
    public function lpropuestapro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Proveedor\Lpropuestapro::class, 'idlpropuestapro', 'idlpropuestapro');
    }
}
