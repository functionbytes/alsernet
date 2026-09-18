<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Configuracion\FamiliaCl;
use Modules\Erp\Traits\UsesOCI8Performance;
use Modules\Supplier\Entities\SupplierErpCategory;

/**
 * Modelo para la tabla CATEGORIA_CL
 *
 * ÍNDICES DISPONIBLES:
 * PK_CATEGORIA_CL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCATEGORIA_CL
 */
class CategoriaCl extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'categoria_cl';

    protected $primaryKey = 'idcategoria_cl';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'iddeporte_cl', 'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
        'descripcion', 'desc_corta', 'aparece_inf_stock',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: CategoriaCl
     * ✅ Usa PK_CATEGORIA_CL (indexado)
     */
    public function categoriaCl()
    {
        return $this->belongsTo(CategoriaCl::class, 'idcategoria_cl', 'idcategoria_cl');
    }

    /**
     * Relación: DeporteCl
     * ⚠️  SIN ÍNDICE en IDDEPORTE_CL
     */
    public function deporteCl()
    {
        return $this->belongsTo(DeporteCl::class, 'iddeporte_cl', 'iddeporte_cl');
    }

    /**
     * Relación inversa: SupplierErpCategories (sincronización Supplier)
     */
    public function supplierErpCategories(): HasMany
    {
        return $this->hasMany(SupplierErpCategory::class, 'erp_category_id', 'idcategoria_cl');
    }

    /**
     * Familias de esta categoría
     */
    public function familias()
    {
        return $this->hasMany(FamiliaCl::class, 'idcategoria_cl', 'idcategoria_cl');
    }
}
