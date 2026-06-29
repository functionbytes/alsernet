<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_DEUDACLI_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class RupdDeudacliMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_deudacli_monte2';

    protected $primaryKey = 'iddeudacli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
