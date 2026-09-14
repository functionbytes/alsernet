<?php

namespace Modules\Erp\Models\Oracle\Albaran;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ALBARANPRO_TPVCOR
 *
 * ÍNDICES DISPONIBLES:
 * PK_ALBARANPRO_TPVCOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALBARANPRO
 */
class Albaranpro extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'albaranpro_tpvcor';

    protected $primaryKey = 'idalbaranpro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idpedidopro', 'idproveedor', 'idalmacen', 'idalbarancli', 'idregfiscal',
        'idusuariomod', 'fentrada', 'dto', 'not', 'nalbaranpro',
        'idempleado', 'idseriealbaranpro', 'portes', 'idusuariocre', 'idusuariobaj',
        'nrefalbaranpro', 'tipo', 'idenvio', 'idconversionmoneda', 'idcatalogo',
        'estpowerpick', 'estaubicado', 'observaciones', 'facturadoprovisorio', 'fentrada_real',
        'idalbaranpro_central', 'idalmacen_creacion',
    ];

    protected $casts = [
        'fentrada' => 'datetime',
        'fentrada_real' => 'datetime',
    ];
}
