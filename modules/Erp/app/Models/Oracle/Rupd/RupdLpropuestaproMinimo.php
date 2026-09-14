<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPROPUESTAPRO_MINIMO
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpropuestaproMinimo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpropuestapro_minimo';

    protected $primaryKey = 'idlpropuestapro_minimo';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
