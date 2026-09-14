<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CLIENTEGUIA
 * Tabla de replicación/materialización de Oracle
 */
class RupdClienteguia extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_clienteguia';

    protected $primaryKey = 'idclienteguia';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
