<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_W_PAISES
 * Tabla de replicación/materialización de Oracle
 */
class RupdWPaises extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_w_paises';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
