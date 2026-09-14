<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_FACTURACLI_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class RupdFacturacliCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_facturacli_central';

    protected $primaryKey = 'idfacturacli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
