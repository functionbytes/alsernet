<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_VENCIMIENTOPRO
 * Tabla de replicación/materialización de Oracle
 */
class RupdVencimientopro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_vencimientopro';

    protected $primaryKey = 'idvencimientopro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
