<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_MONEDA
 * Tabla de replicación/materialización de Oracle
 */
class RupdMoneda extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_moneda';

    protected $primaryKey = 'idmoneda';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
