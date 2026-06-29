<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_MANTFIL
 * Tabla de replicación/materialización de Oracle
 */
class RupdMantfil extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_mantfil';

    protected $primaryKey = 'idmantfil';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
