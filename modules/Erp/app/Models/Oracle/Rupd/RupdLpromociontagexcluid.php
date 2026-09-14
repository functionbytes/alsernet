<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPROMOCIONTAGEXCLUID
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpromociontagexcluid extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpromociontagexcluid';

    protected $primaryKey = 'idlpromociontagexcluido';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
