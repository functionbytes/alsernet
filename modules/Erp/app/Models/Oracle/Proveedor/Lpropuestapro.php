<?php

namespace Modules\Erp\Models\Oracle\Proveedor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LPROPUESTAPRO
 *
 * ÍNDICES DISPONIBLES:
 * PK_LPROPUESTAPRO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLPROPUESTAPRO
 */
class Lpropuestapro extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lpropuestapro';

    protected $primaryKey = 'idlpropuestapro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idpropuestapro', 'codigopro', 'descripcion', 'observaciones', 'unidades',
        'not', 'unidadespedir', 'not', 'idarticulo', 'estado',
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'pcosto', 'dto1',
        'dto2', 'precio', 'idgrupo_cl', 'idcatalogo', 'idimpuesto',
        'codbar', 'idlpedidocli', 'preciorecomendadoprov', 'upc', 'idmodelo',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Lpropuestapro
     * ✅ Usa PK_LPROPUESTAPRO (indexado)
     */
    public function lpropuestapro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Proveedor\Lpropuestapro::class, 'idlpropuestapro', 'idlpropuestapro');
    }

    /**
     * Relación: Propuestapro
     * ⚠️  SIN ÍNDICE en IDPROPUESTAPRO
     */
    public function propuestapro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Proveedor\Propuestapro::class, 'idpropuestapro', 'idpropuestapro');
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
     * Relación: GrupoCl
     * ⚠️  SIN ÍNDICE en IDGRUPO_CL
     */
    public function grupoCl()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\GrupoCl::class, 'idgrupo_cl', 'idgrupo_cl');
    }

    /**
     * Relación: Catalogo
     * ⚠️  SIN ÍNDICE en IDCATALOGO
     */
    public function catalogo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Catalogo\Catalogo::class, 'idcatalogo', 'idcatalogo');
    }

    /**
     * Relación: Impuesto
     * ⚠️  SIN ÍNDICE en IDIMPUESTO
     */
    public function impuesto()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Impuesto::class, 'idimpuesto', 'idimpuesto');
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
     * Relación: Modelo
     * ⚠️  SIN ÍNDICE en IDMODELO
     */
    public function modelo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Catalogo\Modelo::class, 'idmodelo', 'idmodelo');
    }
}
