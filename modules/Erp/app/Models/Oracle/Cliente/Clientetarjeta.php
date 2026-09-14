<?php

namespace Modules\Erp\Models\Oracle\Cliente;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CLIENTETARJETA_CENT
 *
 * ÍNDICES DISPONIBLES:
 * ✅ FK_CLIENTETAR_CENT__CLIENTE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTE
 *
 * PK_CLIENTETAR_CENT (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTETARJETA
 */
class Clientetarjeta extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'clientetarjeta_cent';

    protected $primaryKey = 'idclientetarjeta';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcliente', 'idtarjeta', 'idusuariocre', 'idusuariobaj', 'idusuariomod',
        'estado', 'numerotarjeta', 'idbanco', 'nombretitular', 'fcaducidad',
        'limite', 'idmoneda', 'observacion', 'cvv',
    ];

    protected $casts = [
        'fcaducidad' => 'datetime',
        'estado' => 'boolean',
    ];
}
