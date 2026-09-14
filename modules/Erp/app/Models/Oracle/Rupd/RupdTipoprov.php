<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TIPOPROV
 * Tabla de replicación/materialización de Oracle
 */
class RupdTipoprov extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tipoprov';

    protected $primaryKey = 'idtipoprov';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
