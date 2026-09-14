<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_W_CARACTERISTICAS_PR1
 * Tabla de replicación/materialización de Oracle
 */
class RupdWCaracteristicasPr1 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_w_caracteristicas_pr1';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
