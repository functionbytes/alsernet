<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPROMOCION
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpromocion extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpromocion';

    protected $primaryKey = 'idlpromocion';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
