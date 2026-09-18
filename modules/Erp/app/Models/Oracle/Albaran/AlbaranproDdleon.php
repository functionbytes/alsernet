<?php

namespace Modules\Erp\Models\Oracle\Albaran;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Catalogo\Catalogo;
use Modules\Erp\Models\Oracle\Configuracion\Almacen;
use Modules\Erp\Models\Oracle\Configuracion\Conversionmoneda;
use Modules\Erp\Models\Oracle\Configuracion\Regfiscal;
use Modules\Erp\Models\Oracle\Pedido\PedidoproCapthaya;
use Modules\Erp\Models\Oracle\Proveedor\Proveedor;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ALBARANPRO_DDLEON
 *
 * ÍNDICES DISPONIBLES:
 * PK_ALBARANPRO_DDLEON (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALBARANPRO
 */
class AlbaranproDdleon extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'albaranpro_ddleon';

    protected $primaryKey = 'idalbaranpro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idpedidopro', 'idproveedor', 'idalmacen', 'idalbarancli', 'idregfiscal',
        'idusuariomod', 'fentrada', 'dto', 'not', 'nalbaranpro',
        'idempleado', 'idseriealbaranpro', 'portes', 'idusuariocre', 'idusuariobaj',
        'nrefalbaranpro', 'tipo', 'idenvio', 'idconversionmoneda', 'idcatalogo',
        'estpowerpick', 'estaubicado', 'observaciones', 'facturadoprovisorio', 'fentrada_real',
        'idalbaranpro_central', 'idalmacen_creacion',
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
     * ✅ Usa PK_ALBARANPRO_DDLEON (indexado)
     */
    public function albaranpro()
    {
        return $this->belongsTo(AlbaranproCapthaya::class, 'idalbaranpro', 'idalbaranpro');
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
     * Relación: Albarancli
     * ⚠️  SIN ÍNDICE en IDALBARANCLI
     */
    public function albarancli()
    {
        return $this->belongsTo(AlbarancliCapthaya::class, 'idalbarancli', 'idalbarancli');
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
     * Relación: Conversionmoneda
     * ⚠️  SIN ÍNDICE en IDCONVERSIONMONEDA
     */
    public function conversionmoneda()
    {
        return $this->belongsTo(Conversionmoneda::class, 'idconversionmoneda', 'idconversionmoneda');
    }

    /**
     * Relación: Catalogo
     * ⚠️  SIN ÍNDICE en IDCATALOGO
     */
    public function catalogo()
    {
        return $this->belongsTo(Catalogo::class, 'idcatalogo', 'idcatalogo');
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
