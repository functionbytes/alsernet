<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_PROPUESTAPRO
 * Tabla de replicación/materialización de Oracle
 */
class RupdPropuestapro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_propuestapro';

    protected $primaryKey = 'idpropuestapro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
