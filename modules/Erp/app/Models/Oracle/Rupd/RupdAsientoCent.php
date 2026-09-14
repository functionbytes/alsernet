<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_ASIENTO_CENT
 * Tabla de replicación/materialización de Oracle
 */
class RupdAsientoCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_asiento_cent';

    protected $primaryKey = 'idasiento';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
