<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_ZONA_POSTAL
 * Tabla de replicación/materialización de Oracle
 */
class RupdZonaPostal extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_zona_postal';

    protected $primaryKey = 'idzona_postal';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
