<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TIPOCAJA
 * Tabla de replicación/materialización de Oracle
 */
class RupdTipocaja extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tipocaja';

    protected $primaryKey = 'idtipocaja';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
