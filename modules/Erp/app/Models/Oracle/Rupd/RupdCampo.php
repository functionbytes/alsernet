<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CAMPO
 * Tabla de replicación/materialización de Oracle
 */
class RupdCampo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_campo';

    protected $primaryKey = 'idcampo';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
