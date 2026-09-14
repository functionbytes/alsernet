<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_APUNTE_CENT
 * Tabla de replicación/materialización de Oracle
 */
class RupdApunteCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_apunte_cent';

    protected $primaryKey = 'idapunte';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
