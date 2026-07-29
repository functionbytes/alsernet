<?php

namespace Modules\Erp\Models\Oracle\Lote;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Configuracion\Idioma;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LLOTEIDIOMA
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_LLOTEIDIOMA_IDIDIOMA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDIDIOMA
 *
 * PK_LLOTEIDIOMA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLLOTEIDIOMA
 *
 * ✅ UK_LLOTEIDIOMA_LLOTE_IDIOMA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLLOTE, IDIDIOMA
 */
class Lloteidioma extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lloteidioma';

    protected $primaryKey = 'idlloteidioma';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idllote', 'ididioma', 'idusuariocre', 'idusuariobaj', 'idusuariomod',
        'estado', 'descripcion',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Idioma
     */
    public function ioma()
    {
        return $this->belongsTo(Idioma::class, 'ididioma', 'ididioma');
    }

    /**
     * Relación con Llote
     */
    public function llote()
    {
        return $this->belongsTo(Llote::class, 'idllote', 'idllote');
    }

    /**
     * Relación: Idioma
     * ✅ Usa IDX_LLOTEIDIOMA_IDIDIOMA (indexado)
     */
    public function idioma()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Idioma::class, 'ididioma', 'ididioma');
    }

    /**
     * Relación: Lloteidioma
     * ✅ Usa PK_LLOTEIDIOMA (indexado)
     */
    public function lloteidioma()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Lote\Lloteidioma::class, 'idlloteidioma', 'idlloteidioma');
    }
}
