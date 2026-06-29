<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CLIENTECATALOGO_CENT
 * Tabla de replicación/materialización de Oracle
 */
class RupdClientecatalogoCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_clientecatalogo_cent';

    protected $primaryKey = 'idclientecatalogo';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
