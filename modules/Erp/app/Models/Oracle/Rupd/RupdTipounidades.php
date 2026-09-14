<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TIPOUNIDADES
 * Tabla de replicación/materialización de Oracle
 */
class RupdTipounidades extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tipounidades';

    protected $primaryKey = 'idtipounidades';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
