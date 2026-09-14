<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CATALOGO
 * Tabla de replicación/materialización de Oracle
 */
class RupdCatalogo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_catalogo';

    protected $primaryKey = 'idcatalogo';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
