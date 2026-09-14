<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LLOTE
 * Tabla de replicación/materialización de Oracle
 */
class RupdLlote extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_llote';

    protected $primaryKey = 'idllote';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
