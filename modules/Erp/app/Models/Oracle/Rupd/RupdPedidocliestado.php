<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_PEDIDOCLIESTADO
 * Tabla de replicación/materialización de Oracle
 */
class RupdPedidocliestado extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_pedidocliestado';

    protected $primaryKey = 'estado';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
