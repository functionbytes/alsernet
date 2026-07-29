<?php

namespace Modules\Erp\Models\Oracle\Factura;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LFACTURAPRO
 *
 * ÍNDICES DISPONIBLES:
 * PK_LFACTURAPRO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLFACTURAPRO
 */
class Lfacturapro extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lfacturapro';

    protected $primaryKey = 'idlfacturapro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idfacturapro', 'idlalbaranpro', 'codigo', 'descripcion', 'unidades',
        'not', 'precio', 'not', 'iva', 'not',
        'recargo', 'not', 'dto', 'not', 'idusuariomod',
        'idtipomedida', 'unid', 'dto2', 'idcatalogo', 'idalmacen',
        'idlalbaranpro_central',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Lfacturapro
     * ✅ Usa PK_LFACTURAPRO (indexado)
     */
    public function lfacturapro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Factura\Lfacturapro::class, 'idlfacturapro', 'idlfacturapro');
    }

    /**
     * Relación: Facturapro
     * ⚠️  SIN ÍNDICE en IDFACTURAPRO
     */
    public function facturapro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Factura\Facturapro::class, 'idfacturapro', 'idfacturapro');
    }

    /**
     * Relación: Lalbaranpro
     * ⚠️  SIN ÍNDICE en IDLALBARANPRO
     */
    public function lalbaranpro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\LalbaranproCapthaya::class, 'idlalbaranpro', 'idlalbaranpro');
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
     * Relación: Catalogo
     * ⚠️  SIN ÍNDICE en IDCATALOGO
     */
    public function catalogo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Catalogo\Catalogo::class, 'idcatalogo', 'idcatalogo');
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
     * Relación: LalbaranproCentral
     * ⚠️  SIN ÍNDICE en IDLALBARANPRO_CENTRAL
     */
    public function lalbaranproCentral()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\LalbaranproCentral::class, 'idlalbaranpro_central', 'idlalbaranpro_central');
    }
}
