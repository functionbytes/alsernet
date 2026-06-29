<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPROMOCIONTAGINCLUID
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpromociontagincluid extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpromociontagincluid';

    protected $primaryKey = 'idlpromociontagincluido';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
