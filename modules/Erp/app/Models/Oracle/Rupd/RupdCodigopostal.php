<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CODIGOPOSTAL
 * Tabla de replicación/materialización de Oracle
 */
class RupdCodigopostal extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_codigopostal';

    protected $primaryKey = 'idcodigopostal';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
