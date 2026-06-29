<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Models\Oracle\Articulo\Articulo;
use Modules\Erp\Models\Oracle\Configuracion\Tipomedida;
use Modules\Erp\Models\Oracle\Proveedor\Lpropuestapro;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LPEDIDOPRO_TPVCOR
 *
 * ÍNDICES DISPONIBLES:
 * PK_LPEDIDOPRO_TPVCOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLPEDIDOPRO
 */
class Lpedidopro extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lpedidopro_tpvcor';

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
     * ✅ Usa PK_LPEDIDOPRO_TPVCOR (indexado)
     */
    public function lpedidopro()
    {
        return $this->belongsTo(LpedidoproCapthaya::class, 'idlpedidopro', 'idlpedidopro');
    }

    /**
     * Relación: Pedidopro
     * ⚠️  SIN ÍNDICE en IDPEDIDOPRO
     */
    public function pedidopro()
    {
        return $this->belongsTo(PedidoproCapthaya::class, 'idpedidopro', 'idpedidopro');
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
     * Relación: Lpropuestapro
     * ⚠️  SIN ÍNDICE en IDLPROPUESTAPRO
     */
    public function lpropuestapro()
    {
        return $this->belongsTo(Lpropuestapro::class, 'idlpropuestapro', 'idlpropuestapro');
    }
}
