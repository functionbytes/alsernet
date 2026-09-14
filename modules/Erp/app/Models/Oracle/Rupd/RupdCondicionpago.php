<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CONDICIONPAGO
 * Tabla de replicación/materialización de Oracle
 */
class RupdCondicionpago extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_condicionpago';

    protected $primaryKey = 'idcondicionpago';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
