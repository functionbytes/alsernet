<?php

namespace Modules\Erp\Models\Oracle\Albaran;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Models\Oracle\Articulo\Articulo;
use Modules\Erp\Models\Oracle\Configuracion\Tipomedida;
use Modules\Erp\Models\Oracle\Pedido\LpedidoproCapthaya;
use Modules\Erp\Models\Oracle\Pedido\LpedidoproCentral;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LALBARANPRO_CENTRAL
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_IDLALBPRO_CENT_ALBPRO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALBARANPRO_CENTRAL
 *
 * ✅ IDX_IDLALBPRO_CENT_ART (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTICULO
 *
 * PK_LALBARANPRO_CENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLALBARANPRO_CENTRAL
 */
class LalbaranproCentral extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lalbaranpro_central';

    protected $primaryKey = 'idlalbaranpro_central';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    protected $fillable = [
        'idlalbaranpro', 'idmovalm', 'idlpedidopro', 'idalbaranpro', 'idarticulo',
        'idusuariomod', 'unidades', 'unidalb', 'precio', 'dto',
        'iva', 'recargo', 'idtipomedida', 'preciocosto', 'unid',
        'notapieza', 'dto2', 'idlalbaranclireparacion', 'preciomonedaoriginal', 'idalbaranpro_central',
        'idalmacen_creacion', 'idlpedidopro_central', 'ubicacion', 'estaubicado', 'numero_serie',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Lalbaranpro
     * ⚠️  SIN ÍNDICE en IDLALBARANPRO
     */
    public function lalbaranpro()
    {
        return $this->belongsTo(LalbaranproCapthaya::class, 'idlalbaranpro', 'idlalbaranpro');
    }

    /**
     * Relación: Lpedidopro
     * ⚠️  SIN ÍNDICE en IDLPEDIDOPRO
     */
    public function lpedidopro()
    {
        return $this->belongsTo(LpedidoproCapthaya::class, 'idlpedidopro', 'idlpedidopro');
    }

    /**
     * Relación: Albaranpro
     * ⚠️  SIN ÍNDICE en IDALBARANPRO
     */
    public function albaranpro()
    {
        return $this->belongsTo(AlbaranproCapthaya::class, 'idalbaranpro', 'idalbaranpro');
    }

    /**
     * Relación: Articulo
     * ✅ Usa IDX_IDLALBPRO_CENT_ART (indexado)
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
     * Relación: LalbaranproCentral
     * ✅ Usa PK_LALBARANPRO_CENTRAL (indexado)
     */
    public function lalbaranproCentral()
    {
        return $this->belongsTo(LalbaranproCentral::class, 'idlalbaranpro_central', 'idlalbaranpro_central');
    }

    /**
     * Relación: AlbaranproCentral
     * ✅ Usa IDX_IDLALBPRO_CENT_ALBPRO (indexado)
     */
    public function albaranproCentral()
    {
        return $this->belongsTo(AlbaranproCentral::class, 'idalbaranpro_central', 'idalbaranpro_central');
    }

    /**
     * Relación: LpedidoproCentral
     * ⚠️  SIN ÍNDICE en IDLPEDIDOPRO_CENTRAL
     */
    public function lpedidoproCentral()
    {
        return $this->belongsTo(LpedidoproCentral::class, 'idlpedidopro_central', 'idlpedidopro_central');
    }
}
