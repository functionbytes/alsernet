<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CIERRE_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class RupdCierreCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_cierre_central';

    protected $primaryKey = 'idcierre';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
