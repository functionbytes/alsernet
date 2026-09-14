<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TTARIFA
 * Tabla de replicación/materialización de Oracle
 */
class RupdTtarifa extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_ttarifa';

    protected $primaryKey = 'idttarifa';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
