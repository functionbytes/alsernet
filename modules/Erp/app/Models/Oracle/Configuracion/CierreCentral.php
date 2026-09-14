<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CIERRE_CENTRAL
 *
 * ÍNDICES DISPONIBLES:
 * PK_CIERRE_CENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCIERRE
 */
class CierreCentral extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'cierre_central';

    protected $primaryKey = 'idcierre';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcaja', 'estado', 'idusuariomod', 'imp_inicial', 'observaciones',
        'fcierre', 'fapertura', 'ncierre', 'idempleado', 'idcierrec',
        'idalmacen', 'idasiento',
    ];

    protected $casts = [
        'fcierre' => 'datetime',
        'fapertura' => 'datetime',
        'estado' => 'boolean',
    ];
}
