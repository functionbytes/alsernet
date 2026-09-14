<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla PEDIDOCLI_TPVCOR
 *
 * ÍNDICES DISPONIBLES:
 * PK_PEDIDOCLI_TPVCOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPEDIDOCLI
 */
class Pedidocli extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'pedidocli_tpvcor';

    protected $primaryKey = 'idpedidocli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idalmacen', 'idcliente', 'estado', 'fpedido', 'fcomreserva',
        'fliberacion', 'observaciones', 'idusuariomod', 'idregfiscal', 'idempleado',
        'idseriepedidocli', 'npedidocli', 'tiporiesgo', 'idprioridad', 'idenvio',
        'idorigenpedidocli', 'idusuariocre', 'idusuariobaj', 'idcatalogo', 'idultimaincidencia',
        'fprevista', 'fservido', 'clientetelefono', 'numeroserie', 'identificadororigen',
        'solicitafactura', 'concartuchos', 'servirincompleto', 'facturado', 'revisadotransp',
        'tipopedido', 'idregpais', 'idtmotivoanulacionpedido', 'idafiliado', 'texto_regalo',
        'idclientecuenta', 'es_compromiso_alvarez', 'idprefijo_telefono',
    ];

    protected $casts = [
        'fpedido' => 'datetime',
        'fcomreserva' => 'datetime',
        'fliberacion' => 'datetime',
        'fprevista' => 'datetime',
        'fservido' => 'datetime',
        'estado' => 'boolean',
    ];
}
