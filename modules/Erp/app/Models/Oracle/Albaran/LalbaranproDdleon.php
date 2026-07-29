<?php

namespace Modules\Erp\Models\Oracle\Albaran;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LALBARANPRO_DDLEON
 *
 * ÍNDICES DISPONIBLES:
 * PK_LALBARANPRO_DDLEON (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLALBARANPRO
 */
class LalbaranproDdleon extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lalbaranpro_ddleon';

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
     * ✅ Usa PK_LALBARANPRO_DDLEON (indexado)
     */
    public function lalbaranpro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\LalbaranproCapthaya::class, 'idlalbaranpro', 'idlalbaranpro');
    }

    /**
     * Relación: Lpedidopro
     * ⚠️  SIN ÍNDICE en IDLPEDIDOPRO
     */
    public function lpedidopro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\LpedidoproCapthaya::class, 'idlpedidopro', 'idlpedidopro');
    }

    /**
     * Relación: Albaranpro
     * ⚠️  SIN ÍNDICE en IDALBARANPRO
     */
    public function albaranpro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\AlbaranproCapthaya::class, 'idalbaranpro', 'idalbaranpro');
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
     * Relación: LalbaranproCentral
     * ⚠️  SIN ÍNDICE en IDLALBARANPRO_CENTRAL
     */
    public function lalbaranproCentral()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\LalbaranproCentral::class, 'idlalbaranpro_central', 'idlalbaranpro_central');
    }

    /**
     * Relación: AlbaranproCentral
     * ⚠️  SIN ÍNDICE en IDALBARANPRO_CENTRAL
     */
    public function albaranproCentral()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\AlbaranproCentral::class, 'idalbaranpro_central', 'idalbaranpro_central');
    }
}
