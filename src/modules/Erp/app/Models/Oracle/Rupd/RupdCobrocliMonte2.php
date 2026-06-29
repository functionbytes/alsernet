<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_COBROCLI_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class RupdCobrocliMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_cobrocli_monte2';

    protected $primaryKey = 'idcobrocli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
