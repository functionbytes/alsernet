<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TIPOCLIENTE
 * Tabla de replicación/materialización de Oracle
 */
class RupdTipocliente extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tipocliente';

    protected $primaryKey = 'idtipocliente';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
