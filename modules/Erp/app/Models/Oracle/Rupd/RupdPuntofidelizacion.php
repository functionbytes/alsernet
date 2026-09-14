<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_PUNTOFIDELIZACION
 * Tabla de replicación/materialización de Oracle
 */
class RupdPuntofidelizacion extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_puntofidelizacion';

    protected $primaryKey = 'idpuntofidelizacion';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
