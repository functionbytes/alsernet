<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TRASPASO_TPVCOR
 *
 * ÍNDICES DISPONIBLES:
 * PK_TRASPASO_TPVCOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTRASPASO
 */
class TraspasoTpvcor extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'traspaso_tpvcor';

    protected $primaryKey = 'idtraspaso';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    protected $fillable = [
        'idalmacen', 'alm_idalmacen', 'alm_idalmacen2', 'ftraspaso', 'observaciones',
        'estado', 'idtraspasoorig', 'tipo', 'idusuariomod', 'idserietraspaso',
        'ntraspaso', 'idempleado', 'estpowerpick',
    ];

    protected $casts = [
        'ftraspaso' => 'datetime',
        'estado' => 'boolean',
    ];
}
