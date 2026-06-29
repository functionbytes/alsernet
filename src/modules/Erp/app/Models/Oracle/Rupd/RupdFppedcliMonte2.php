<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_FPPEDCLI_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class RupdFppedcliMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_fppedcli_monte2';

    protected $primaryKey = 'idfppedcli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
