<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_FORMAPAGO_METODO
 * Tabla de replicación/materialización de Oracle
 */
class RupdFormapagoMetodo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_formapago_metodo';

    protected $primaryKey = 'idformapago_metodo';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
