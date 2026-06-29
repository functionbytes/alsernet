<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TIPOALBARANCLI
 * Tabla de replicación/materialización de Oracle
 */
class RupdTipoalbarancli extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tipoalbarancli';

    protected $primaryKey = 'idtipoalbarancli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
