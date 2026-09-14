<?php

namespace Modules\Erp\Models\Oracle\Catalogo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Articulo\ArticuloCatalogoImpreso;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CATALOGO_IMPRESO
 *
 * ÍNDICES DISPONIBLES:
 * PK_CATALOGO_IMPRESO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCATALOGO_IMPRESO
 *
 * ⚠️  UK_CATALOGO_IMPRESO_DESC (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: DESCRIPCION
 */
class CatalogoImpreso extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'catalogo_impreso';

    protected $primaryKey = 'idcatalogo_impreso';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'anno', 'fpublicacion', 'idusuariocre', 'idusuariomod',
        'idusuariobaja', 'estado',
    ];

    protected $casts = [
        'fpublicacion' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con ArticuloCatalogoImpreso
     */
    public function articuloCatalogoImpresos()
    {
        return $this->hasMany(ArticuloCatalogoImpreso::class, 'idcatalogo_impreso', 'idcatalogo_impreso');
    }

    /**
     * Relación: CatalogoImpreso
     * ✅ Usa PK_CATALOGO_IMPRESO (indexado)
     */
    public function catalogoImpreso()
    {
        return $this->belongsTo(CatalogoImpreso::class, 'idcatalogo_impreso', 'idcatalogo_impreso');
    }
}
