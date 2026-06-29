<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TIPOPEDIDOPROVEEDOR
 * Tabla de replicación/materialización de Oracle
 */
class RupdTipopedidoproveedor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tipopedidoproveedor';

    protected $primaryKey = 'idtipopedidoprov';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
