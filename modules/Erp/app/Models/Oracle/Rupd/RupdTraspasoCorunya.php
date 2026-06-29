<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TRASPASO_CORUNYA
 * Tabla de replicación/materialización de Oracle
 */
class RupdTraspasoCorunya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_traspaso_corunya';

    protected $primaryKey = 'idtraspaso';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
