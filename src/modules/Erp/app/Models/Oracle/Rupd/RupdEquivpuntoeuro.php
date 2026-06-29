<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_EQUIVPUNTOEURO
 * Tabla de replicación/materialización de Oracle
 */
class RupdEquivpuntoeuro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_equivpuntoeuro';

    protected $primaryKey = 'idequivpuntoeuro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
