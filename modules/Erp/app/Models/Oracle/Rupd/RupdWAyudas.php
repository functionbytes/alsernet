<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_W_AYUDAS
 * Tabla de replicación/materialización de Oracle
 */
class RupdWAyudas extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_w_ayudas';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
