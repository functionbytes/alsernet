<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_FOTOGRAFIA
 * Tabla de replicación/materialización de Oracle
 */
class RupdFotografia extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_fotografia';

    protected $primaryKey = 'idfotografia';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
