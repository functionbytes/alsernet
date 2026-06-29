<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_ARTICULOCATALOGO
 * Tabla de replicación/materialización de Oracle
 */
class RupdArticulocatalogo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_articulocatalogo';

    protected $primaryKey = 'idarticulocatalogo';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
