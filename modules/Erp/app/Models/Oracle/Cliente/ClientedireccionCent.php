<?php

namespace Modules\Erp\Models\Oracle\Cliente;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CLIENTEDIRECCION_CENT (Direcciones de Cliente)
 *
 * @property int $idclientedireccion Clave primaria (PK)
 * @property int $idcliente Foreign key a CLIENTE_CENT
 * @property int $idtipodireccion Tipo: 1=Envío, 2=Facturación, etc.
 * @property string $codigopostal
 * @property string $poblacion
 * @property string $provincia
 * @property string $pais
 * @property string $calle
 * @property string $num
 *
 * ÍNDICES DISPONIBLES:
 * ✅ FK_CLIENTEDIR_CENT__CLIENTE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTE
 *
 * PK_CLIENTEDIR_CENT (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTEDIRECCION
 */
class ClientedireccionCent extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'clientedireccion_cent';

    protected $primaryKey = 'idclientedireccion';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcliente', 'idtipodireccion', 'idusuariocre', 'idusuariobaj', 'idusuariomod',
        'estado', 'codigopostal', 'poblacion', 'provincia', 'pais',
        'observacion', 'calle', 'num',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];
}
