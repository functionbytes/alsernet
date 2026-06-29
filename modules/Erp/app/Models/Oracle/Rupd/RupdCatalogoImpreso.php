<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CATALOGO_IMPRESO
 * Tabla de replicación/materialización de Oracle
 */
class RupdCatalogoImpreso extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_catalogo_impreso';

    protected $primaryKey = 'idcatalogo_impreso';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
