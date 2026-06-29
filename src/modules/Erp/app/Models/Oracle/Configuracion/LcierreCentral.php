<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LCIERRE_CENTRAL
 *
 * ÍNDICES DISPONIBLES:
 * PK_LCIERRE_CENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLCIERRE
 */
class LcierreCentral extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lcierre_central';

    protected $primaryKey = 'idlcierre';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idformapago', 'idcierre', 'estado', 'idusuariomod', 'importe',
        'not', 'descuadre', 'not', 'idlcierrec', 'idcierrec',
        'remanente',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];
}
