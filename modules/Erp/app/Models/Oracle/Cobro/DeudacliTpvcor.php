<?php

namespace Modules\Erp\Models\Oracle\Cobro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla DEUDACLI_TPVCOR
 *
 * ÍNDICES DISPONIBLES:
 * ✅ INDX_DEUDACLI_TPV_IDALBARANCLI (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALBARANCLI
 *
 * PK_DEUDACLI_TPVCOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDDEUDACLI
 */
class DeudacliTpvcor extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'deudacli_tpvcor';

    protected $primaryKey = 'iddeudacli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcobrocli', 'idalbarancli', 'idformapago', 'idusuariocre', 'idusuariomod',
        'idusuariobaj', 'estado', 'importe',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];
}
