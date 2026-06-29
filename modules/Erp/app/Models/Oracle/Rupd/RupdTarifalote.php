<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TARIFALOTE
 * Tabla de replicación/materialización de Oracle
 */
class RupdTarifalote extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tarifalote';

    protected $primaryKey = 'idtarifalote';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
