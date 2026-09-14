<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla DIARIO_CENT
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_DIARIO_CENT_IDEJCONTABLE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDEJERCICIO_CONTABLE
 *
 * ✅ IDX_DIARIO_CENT_IDTIPODIARIO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTIPODIARIO
 *
 * PK_DIARIO_CENT (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDDIARIO
 */
class DiarioCent extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'diario_cent';

    protected $primaryKey = 'iddiario';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idejercicio_contable', 'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado',
        'supuesto', 'descripcion', 'idtipodiario', 'nasiento',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];
}
