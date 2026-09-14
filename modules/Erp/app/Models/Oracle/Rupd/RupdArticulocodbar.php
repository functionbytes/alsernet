<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_ARTICULOCODBAR
 * Tabla de replicación/materialización de Oracle
 */
class RupdArticulocodbar extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_articulocodbar';

    protected $primaryKey = 'idarticulocodbar';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
