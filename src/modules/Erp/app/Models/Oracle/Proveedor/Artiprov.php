<?php

namespace Modules\Erp\Models\Oracle\Proveedor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Articulo\Articulo;
use Modules\Erp\Traits\UsesOCI8Performance;
use Modules\Supplier\Entities\SupplierProviderProduct;

/**
 * Modelo para la tabla ARTIPROV
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_ARTIPROV_EAN13 (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: EAN13
 *
 * ✅ IDX_ARTIPROV_IDARTICULO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTICULO
 *
 * ✅ IDX_ARTIPROV_IDPROVEEDOR (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPROVEEDOR
 *
 * ✅ IDX_ARTIPROV_UPC (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: UPC
 *
 * PK_ARTIPROV (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTIPROV
 */
class Artiprov extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'artiprov';

    protected $primaryKey = 'idartiprov';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idproveedor', 'idarticulo', 'descripcion', 'codigo', 'pcosto',
        'dto1', 'dto2', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
        'estado', 'pordefecto', 'ean13', 'upc', 'codigo2',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'pordefecto' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Artiprov
     * ✅ Usa PK_ARTIPROV (indexado)
     */
    public function artiprov()
    {
        return $this->belongsTo(Artiprov::class, 'idartiprov', 'idartiprov');
    }

    /**
     * Relación: Proveedor
     * ✅ Usa IDX_ARTIPROV_IDPROVEEDOR (indexado)
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'idproveedor', 'idproveedor');
    }

    /**
     * Relación: Articulo
     * ✅ Usa IDX_ARTIPROV_IDARTICULO (indexado)
     */
    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Relación inversa: SupplierProviderProducts (sincronización Supplier)
     */
    public function supplierProviderProducts(): HasMany
    {
        return $this->hasMany(SupplierProviderProduct::class, 'erp_artiprov_id', 'idartiprov');
    }
}
