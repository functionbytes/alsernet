<?php

namespace Modules\Erp\Models\Oracle\Albaran;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LALBARANCLI_TPVCOR
 *
 * ÍNDICES DISPONIBLES:
 * PK_LALBARANCLI_TPVCOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLALBARANCLI
 */
class Lalbarancli extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lalbarancli_tpvcor';

    protected $primaryKey = 'idlalbarancli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idarticulo', 'idmovalm', 'idalbarancli', 'idusuariomod', 'pcosto',
        'precio', 'not', 'unidades', 'not', 'dto',
        'not', 'iva', 'not', 'recargo', 'not',
        'precioorigen', 'idoferta', 'idalmacen', 'idtipomedida', 'observaciones',
        'unid', 'idlote', 'seclote', 'idlpedidocli', 'notapieza',
        'notageneral', 'idlalbarancliorig', 'idtipodescuento', 'total_bi', 'total_con_impuestos',
        'origen_kardex', 'idbono_promocion', 'guiapertenencia', 'fguiapertenencia', 'narma',
        'ngrupo_segundamano', 'total_neto', 'numero_serie', 'numticket', 'genera_puntos',
        'parte_exenta', 'not', 'tarifa_genera_puntos', 'idempleado_gfitters',
    ];

    protected $casts = [
        'fguiapertenencia' => 'datetime',
    ];
}
