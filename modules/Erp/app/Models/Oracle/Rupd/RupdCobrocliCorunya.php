<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_COBROCLI_CORUNYA
 * Tabla de replicación/materialización de Oracle
 */
class RupdCobrocliCorunya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_cobrocli_corunya';

    protected $primaryKey = 'idcobrocli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
