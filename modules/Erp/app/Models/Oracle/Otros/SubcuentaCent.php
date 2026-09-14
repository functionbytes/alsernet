<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla SUBCUENTA_CENT
 *
 * ÍNDICES DISPONIBLES:
 * PK_SUBCUENTA_CENT (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSUBCUENTA
 */
class SubcuentaCent extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'subcuenta_cent';

    protected $primaryKey = 'idsubcuenta';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'nsubcuenta', 'descripcion', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
        'nif', 'domicilio', 'poblacion', 'provincia', 'cp',
        'intracomunitario', 'recargo_defecto', 'default', 'iva_defecto', 'default',
        'obligar_impuestos', 'idejercicio_contable', 'idempresa', 'estado', 'observacion',
        'idpais', 'retencion_defecto', 'default',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];
}
