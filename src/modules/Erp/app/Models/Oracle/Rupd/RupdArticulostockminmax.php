<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_ARTICULOSTOCKMINMAX
 * Tabla de replicación/materialización de Oracle
 */
class RupdArticulostockminmax extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_articulostockminmax';

    protected $primaryKey = 'idarticulostockminmax';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
