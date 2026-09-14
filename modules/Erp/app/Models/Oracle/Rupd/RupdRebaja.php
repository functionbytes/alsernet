<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_REBAJA
 * Tabla de replicación/materialización de Oracle
 */
class RupdRebaja extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_rebaja';

    protected $primaryKey = 'idrebaja';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
