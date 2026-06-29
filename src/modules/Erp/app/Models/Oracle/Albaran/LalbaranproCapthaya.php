<?php

namespace Modules\Erp\Models\Oracle\Albaran;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Models\Oracle\Articulo\Articulo;
use Modules\Erp\Models\Oracle\Configuracion\Tipomedida;
use Modules\Erp\Models\Oracle\Pedido\LpedidoproCapthaya;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LALBARANPRO_CAPTHAYA
 *
 * ÍNDICES DISPONIBLES:
 * PK_LALBARANPRO_CAPTHAYA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLALBARANPRO
 */
class LalbaranproCapthaya extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lalbaranpro_capthaya';

    protected $primaryKey = 'idlalbaranpro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    protected $fillable = [
        'idmovalm', 'idlpedidopro', 'idalbaranpro', 'idarticulo', 'idusuariomod',
        'unidades', 'not', 'unidalb', 'precio', 'not',
        'dto', 'not', 'iva', 'not', 'recargo',
        'not', 'idtipomedida', 'preciocosto', 'unid', 'notapieza',
        'dto2', 'idlalbaranclireparacion', 'preciomonedaoriginal', 'ubicacion', 'estaubicado',
        'idlalbaranpro_central', 'idalbaranpro_central', 'idalmacen_creacion', 'numero_serie',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Lalbaranpro
     * ✅ Usa PK_LALBARANPRO_CAPTHAYA (indexado)
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
     * Relación: LalbaranproCentral
     * ⚠️  SIN ÍNDICE en IDLALBARANPRO_CENTRAL
     */
    public function lalbaranproCentral()
    {
        return $this->belongsTo(LalbaranproCentral::class, 'idlalbaranpro_central', 'idlalbaranpro_central');
    }

    /**
     * Relación: AlbaranproCentral
     * ⚠️  SIN ÍNDICE en IDALBARANPRO_CENTRAL
     */
    public function albaranproCentral()
    {
        return $this->belongsTo(AlbaranproCentral::class, 'idalbaranpro_central', 'idalbaranpro_central');
    }
}
