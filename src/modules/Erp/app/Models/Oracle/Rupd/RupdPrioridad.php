<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_PRIORIDAD
 * Tabla de replicación/materialización de Oracle
 */
class RupdPrioridad extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_prioridad';

    protected $primaryKey = 'idprioridad';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
