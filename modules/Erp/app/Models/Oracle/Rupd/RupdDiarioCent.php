<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_DIARIO_CENT
 * Tabla de replicación/materialización de Oracle
 */
class RupdDiarioCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_diario_cent';

    protected $primaryKey = 'iddiario';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
