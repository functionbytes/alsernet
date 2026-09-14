<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Otros\GrupoCl;
use Modules\Erp\Models\Oracle\Promocion\Lpromocionsubfamiliaincluida;
use Modules\Erp\Traits\UsesOCI8Performance;
use Modules\Supplier\Entities\SupplierSubfamily;

/**
 * Modelo para la tabla SUBFAMILIA_CL
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_SUBFAMILIA_FAMILIA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFAMILIA_CL
 *
 * PK_SUBFAMILIA_CL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSUBFAMILIA_CL
 */
class SubfamiliaCl extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'subfamilia_cl';

    protected $primaryKey = 'idsubfamilia_cl';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idfamilia_cl', 'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
        'descripcion', 'desc_corta', 'escartucheria', 'esmunicionmetalica', 'mostrarlotes',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con Lpromocionsubfamiliaincluida
     */
    public function lpromocionsubfamiliaincluidas()
    {
        return $this->hasMany(Lpromocionsubfamiliaincluida::class, 'idsubfamilia_cl', 'idsubfamilia_cl');
    }

    /**
     * Relación: SubfamiliaCl
     * ✅ Usa PK_SUBFAMILIA_CL (indexado)
     */
    public function subfamiliaCl()
    {
        return $this->belongsTo(SubfamiliaCl::class, 'idsubfamilia_cl', 'idsubfamilia_cl');
    }

    /**
     * Relación: FamiliaCl
     * ✅ Usa IDX_SUBFAMILIA_FAMILIA (indexado)
     */
    public function familiaCl()
    {
        return $this->belongsTo(FamiliaCl::class, 'idfamilia_cl', 'idfamilia_cl');
    }

    /**
     * Relación inversa: SupplierSubfamilies (sincronización Supplier)
     */
    public function supplierSubfamilies(): HasMany
    {
        return $this->hasMany(SupplierSubfamily::class, 'erp_subfamily_id', 'idsubfamilia_cl');
    }

    /**
     * Grupos de esta subfamilia
     */
    public function grupos()
    {
        return $this->hasMany(GrupoCl::class, 'idsubfamilia_cl', 'idsubfamilia_cl');
    }
}
