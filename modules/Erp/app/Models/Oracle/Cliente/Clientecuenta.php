<?php

namespace Modules\Erp\Models\Oracle\Cliente;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CLIENTECUENTA_CENT
 *
 * ÍNDICES DISPONIBLES:
 * ✅ FK_CLIENTECUE_CENT__CLIENTE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTE
 *
 * PK_CLIENTECUE_CENT (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTECUENTA
 */
class Clientecuenta extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'clientecuenta_cent';

    protected $primaryKey = 'idclientecuenta';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcliente', 'idusuariocre', 'idusuariobaj', 'idusuariomod', 'estado',
        'idbanco', 'entidad_', 'oficina_', 'control_', 'ncuenta_',
        'observacion', 'iban', 'bic',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];
}
