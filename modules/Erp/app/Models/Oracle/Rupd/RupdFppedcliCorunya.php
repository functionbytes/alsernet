<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_FPPEDCLI_CORUNYA
 * Tabla de replicación/materialización de Oracle
 */
class RupdFppedcliCorunya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_fppedcli_corunya';

    protected $primaryKey = 'idfppedcli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
