<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_VALORES_NAV
 *
 * ÍNDICES DISPONIBLES:
 * PK_W_VALORES_NAV (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WValoresNav extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_valores_nav';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'nombre', 'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaja',
        'seo_title', 'seo_metadescriptions', 'seo_texto_superior', 'seo_texto_inferior',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con WDescuentosRelacionValor
     */
    public function wDescuentosRelacionValors()
    {
        return $this->hasMany(WDescuentosRelacionValor::class, 'id_valor', 'idw_valores_nav');
    }

    /**
     * Relación inversa con WPerfilesNav
     */
    public function wPerfilesNavs()
    {
        return $this->hasMany(WPerfilesNav::class, 'id_valor', 'idw_valores_nav');
    }

    /**
     * Relación inversa con WValoresNavIdioma
     */
    public function wValoresNavIdiomas()
    {
        return $this->hasMany(WValoresNavIdioma::class, 'id_valor', 'idw_valores_nav');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_VALORES_NAV (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id', 'id');
    }
}
