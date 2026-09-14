<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Articulo\Articulo;
use Modules\Erp\Models\Oracle\Configuracion\SubfamiliaCl;
use Modules\Erp\Traits\UsesOCI8Performance;
use Modules\Supplier\Entities\SupplierGroup;

/**
 * Modelo para la tabla GRUPO_CL
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_GRUPO_SUBFAMILIA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSUBFAMILIA_CL
 *
 * PK_GRUPO_CL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDGRUPO_CL
 */
class GrupoCl extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'grupo_cl';

    protected $primaryKey = 'idgrupo_cl';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idsubfamilia_cl', 'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
        'descripcion', 'desc_corta', 'prefijo', 'prox_num', 'excluir_pedir_a_tienda',
        'pedir_numero_serie', 'intrastat',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: GrupoCl
     * ✅ Usa PK_GRUPO_CL (indexado)
     */
    public function grupoCl()
    {
        return $this->belongsTo(GrupoCl::class, 'idgrupo_cl', 'idgrupo_cl');
    }

    /**
     * Relación: SubfamiliaCl
     * ✅ Usa IDX_GRUPO_SUBFAMILIA (indexado)
     */
    public function subfamiliaCl()
    {
        return $this->belongsTo(SubfamiliaCl::class, 'idsubfamilia_cl', 'idsubfamilia_cl');
    }

    /**
     * Relación inversa: SupplierGroups (sincronización Supplier)
     */
    public function supplierGroups(): HasMany
    {
        return $this->hasMany(SupplierGroup::class, 'erp_group_id', 'idgrupo_cl');
    }

    /**
     * Artículos de este grupo
     */
    public function articulos()
    {
        return $this->hasMany(Articulo::class, 'idgrupo_cl', 'idgrupo_cl');
    }
}
