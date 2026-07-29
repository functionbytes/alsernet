<?php

namespace Modules\Erp\Models\Oracle\Albaran;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ALBARANPRO_CENTRAL
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_IDALBPRO_CENT_ALMACEN (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALMACEN
 *
 * ✅ IDX_IDALBPRO_CENT_FECHA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: FENTRADA
 *
 * PK_ALBARANPRO_CENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALBARANPRO_CENTRAL
 */
class AlbaranproCentral extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'albaranpro_central';

    protected $primaryKey = 'idalbaranpro_central';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idalbaranpro', 'idpedidopro', 'idproveedor', 'idalmacen', 'idalbarancli',
        'idregfiscal', 'idusuariomod', 'fentrada', 'dto', 'nalbaranpro',
        'idempleado', 'idseriealbaranpro', 'portes', 'idusuariocre', 'idusuariobaj',
        'nrefalbaranpro', 'tipo', 'idenvio', 'idconversionmoneda', 'idcatalogo',
        'estpowerpick', 'estaubicado', 'idalmacen_creacion', 'fentrada_real',
    ];

    protected $casts = [
        'fentrada' => 'datetime',
        'fentrada_real' => 'datetime',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Albaranpro
     * ⚠️  SIN ÍNDICE en IDALBARANPRO
     */
    public function albaranpro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\AlbaranproCapthaya::class, 'idalbaranpro', 'idalbaranpro');
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
     * Relación: Proveedor
     * ⚠️  SIN ÍNDICE en IDPROVEEDOR
     */
    public function proveedor()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Proveedor\Proveedor::class, 'idproveedor', 'idproveedor');
    }

    /**
     * Relación: Almacen
     * ✅ Usa IDX_IDALBPRO_CENT_ALMACEN (indexado)
     */
    public function almacen()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Almacen::class, 'idalmacen', 'idalmacen');
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
     * Relación: Regfiscal
     * ⚠️  SIN ÍNDICE en IDREGFISCAL
     */
    public function regfiscal()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Regfiscal::class, 'idregfiscal', 'idregfiscal');
    }

    /**
     * Relación: Conversionmoneda
     * ⚠️  SIN ÍNDICE en IDCONVERSIONMONEDA
     */
    public function conversionmoneda()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Conversionmoneda::class, 'idconversionmoneda', 'idconversionmoneda');
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
     * Relación: AlbaranproCentral
     * ✅ Usa PK_ALBARANPRO_CENTRAL (indexado)
     */
    public function albaranproCentral()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\AlbaranproCentral::class, 'idalbaranpro_central', 'idalbaranpro_central');
    }
}
