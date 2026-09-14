<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_COBROCLI_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class RupdCobrocliCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_cobrocli_central';

    protected $primaryKey = 'idcobrocli_central';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
