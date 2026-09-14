<?php

namespace Modules\Erp\Models\Oracle\Cliente;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CLIENTETELEFONO_CENT
 *
 * ÍNDICES DISPONIBLES:
 * ✅ FK_CLIENTETEL_CENT__CLIENTE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTE
 *
 * PK_CLIENTETEL_CENT (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTETELEFONO
 */
class Clientetelefono extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'clientetelefono_cent';

    protected $primaryKey = 'idclientetelefono';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcliente', 'idtipotelefono', 'idusuariocre', 'idusuariobaj', 'idusuariomod',
        'estado', 'telefono', 'horario', 'observacion', 'envio_sms',
        'idprefijo_telefono',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];
}
