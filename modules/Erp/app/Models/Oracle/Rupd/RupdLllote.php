<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LLLOTE
 * Tabla de replicación/materialización de Oracle
 */
class RupdLllote extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lllote';

    protected $primaryKey = 'idlllote';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
