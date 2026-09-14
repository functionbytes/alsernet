<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_ARTICULOIDIOMA
 * Tabla de replicación/materialización de Oracle
 */
class RupdArticuloidioma extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_articuloidioma';

    protected $primaryKey = 'idarticuloidioma';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
