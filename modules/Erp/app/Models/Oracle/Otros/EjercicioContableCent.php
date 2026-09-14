<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla EJERCICIO_CONTABLE_CENT
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_EJ_CONTABLE_CENT_DIARIO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDDIARIODEFECTO
 *
 * PK_EJERCICIO_CONTABLE_CENT (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDEJERCICIO_CONTABLE
 */
class EjercicioContableCent extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'ejercicio_contable_cent';

    protected $primaryKey = 'idejercicio_contable';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idempresa', 'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado',
        'descripcion', 'finicio', 'ffin', 'iddiariodefecto', 'fecha_bloqueo',
        'codenlace',
    ];

    protected $casts = [
        'finicio' => 'datetime',
        'ffin' => 'datetime',
        'fecha_bloqueo' => 'datetime',
        'estado' => 'boolean',
    ];
}
