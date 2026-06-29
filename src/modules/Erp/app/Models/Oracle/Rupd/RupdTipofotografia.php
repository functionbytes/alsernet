<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TIPOFOTOGRAFIA
 * Tabla de replicación/materialización de Oracle
 */
class RupdTipofotografia extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tipofotografia';

    protected $primaryKey = 'idtipofotografia';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
