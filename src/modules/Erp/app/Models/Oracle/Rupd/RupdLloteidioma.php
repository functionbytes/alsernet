<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LLOTEIDIOMA
 * Tabla de replicación/materialización de Oracle
 */
class RupdLloteidioma extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lloteidioma';

    protected $primaryKey = 'idlloteidioma';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
