<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_COBROCLI_DDLEON
 * Tabla de replicación/materialización de Oracle
 */
class RupdCobrocliDdleon extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_cobrocli_ddleon';

    protected $primaryKey = 'idcobrocli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
