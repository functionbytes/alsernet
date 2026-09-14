<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TRASPASO_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class RupdTraspasoMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_traspaso_monte2';

    protected $primaryKey = 'idtraspaso';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
