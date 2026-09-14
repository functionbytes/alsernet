<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TMOVALM
 * Tabla de replicación/materialización de Oracle
 */
class RupdTmovalm extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tmovalm';

    protected $primaryKey = 'idtmovalm';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
