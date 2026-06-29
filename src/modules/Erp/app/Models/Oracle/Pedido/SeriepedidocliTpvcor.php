<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla SERIEPEDIDOCLI_TPVCOR
 *
 * ÍNDICES DISPONIBLES:
 * PK_SERIEPEDIDOCLI_TPVCOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSERIEPEDIDOCLI
 */
class SeriepedidocliTpvcor extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'seriepedidocli_tpvcor';

    protected $primaryKey = 'idseriepedidocli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'descripcorta', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
        'estado', 'numero', 'idempresa', 'fdesde', 'fhasta',
    ];

    protected $casts = [
        'fdesde' => 'datetime',
        'fhasta' => 'datetime',
        'estado' => 'boolean',
    ];
}
