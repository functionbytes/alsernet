<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPROMOCIONBLOQUEA
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpromocionbloquea extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpromocionbloquea';

    protected $primaryKey = 'idlpromocionbloquea';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
