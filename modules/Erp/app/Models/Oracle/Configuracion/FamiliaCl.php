<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Otros\CategoriaCl;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla FAMILIA_CL
 *
 * ÍNDICES DISPONIBLES:
 * PK_FAMILIA_CL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFAMILIA_CL
 */
class FamiliaCl extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'familia_cl';

    protected $primaryKey = 'idfamilia_cl';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcategoria_cl', 'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
        'descripcion', 'desc_corta', 'sonarmas', 'sonarmasfogueo', 'soncartuchos',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: FamiliaCl
     * ✅ Usa PK_FAMILIA_CL (indexado)
     */
    public function familiaCl()
    {
        return $this->belongsTo(FamiliaCl::class, 'idfamilia_cl', 'idfamilia_cl');
    }

    /**
     * Relación: CategoriaCl
     * ⚠️  SIN ÍNDICE en IDCATEGORIA_CL
     */
    public function categoriaCl()
    {
        return $this->belongsTo(CategoriaCl::class, 'idcategoria_cl', 'idcategoria_cl');
    }

    /**
     * Subfamilias de esta familia
     */
    public function subfamilias()
    {
        return $this->hasMany(SubfamiliaCl::class, 'idfamilia_cl', 'idfamilia_cl');
    }
}
