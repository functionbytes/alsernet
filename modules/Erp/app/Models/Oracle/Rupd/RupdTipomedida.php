<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TIPOMEDIDA
 * Tabla de replicación/materialización de Oracle
 */
class RupdTipomedida extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tipomedida';

    protected $primaryKey = 'idtipomedida';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
