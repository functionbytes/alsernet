<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_DEUDAPRO
 * Tabla de replicación/materialización de Oracle
 */
class RupdDeudapro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_deudapro';

    protected $primaryKey = 'iddeudapro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
