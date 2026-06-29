<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LFACTURAPRO
 * Tabla de replicación/materialización de Oracle
 */
class RupdLfacturapro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lfacturapro';

    protected $primaryKey = 'idlfacturapro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
