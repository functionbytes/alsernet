<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CALIBRE
 * Tabla de replicación/materialización de Oracle
 */
class RupdCalibre extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_calibre';

    protected $primaryKey = 'idcalibre';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
