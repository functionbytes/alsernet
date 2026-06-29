<?php

namespace Modules\Erp\Models\Oracle\Cobro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla COBROCLI_TPVCOR
 *
 * ÍNDICES DISPONIBLES:
 * PK_COBROCLI_TPVCOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCOBROCLI
 */
class Cobrocli extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'cobrocli_tpvcor';

    protected $primaryKey = 'idcobrocli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado', 'importe_cobrado',
        'not', 'importe_libre', 'not', 'fcobro', 'idformapago',
        'idtransportista', 'idvale', 'idcaja', 'idmovcaja', 'idcliente',
        'idasiento', 'segundamano',
    ];

    protected $casts = [
        'fcobro' => 'datetime',
        'estado' => 'boolean',
    ];
}
