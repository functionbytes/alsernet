<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LZONA_POSTAL
 * Tabla de replicación/materialización de Oracle
 */
class RupdLzonaPostal extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lzona_postal';

    protected $primaryKey = 'idlzona_postal';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
