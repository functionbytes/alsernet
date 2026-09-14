<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_SUBCUENTA_CENT
 * Tabla de replicación/materialización de Oracle
 */
class RupdSubcuentaCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_subcuenta_cent';

    protected $primaryKey = 'idsubcuenta';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
