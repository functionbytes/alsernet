<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Web\WAyudas;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TAG_X_TABLA
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_TAG_X_TABLA_ID (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 *
 * ✅ IDX_TAG_X_TABLA_IDTAG (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTAG
 *
 * PK_TAG_X_TABLA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTAG_X_TABLA
 *
 * ✅ UK_TAG_X_TABLA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTAG, ID, ESTADO
 */
class TagXTabla extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tag_x_tabla';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idtag_x_tabla', 'idtag', 'estado', 'idusuariocre', 'idusuariomod',
        'idusuariobaj',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: TagXTabla
     * ✅ Usa PK_TAG_X_TABLA (indexado)
     */
    public function tagXTabla()
    {
        return $this->belongsTo(TagXTabla::class, 'idtag_x_tabla', 'idtag_x_tabla');
    }

    /**
     * Relación: Tag
     * ✅ Usa IDX_TAG_X_TABLA_IDTAG (indexado)
     */
    public function tag()
    {
        return $this->belongsTo(Tag::class, 'idtag', 'idtag');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa IDX_TAG_X_TABLA_ID (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(WAyudas::class, 'id', 'id');
    }
}
