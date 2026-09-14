<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TMOTIVOANULACIONPEDI
 * Tabla de replicación/materialización de Oracle
 */
class RupdTmotivoanulacionpedi extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tmotivoanulacionpedi';

    protected $primaryKey = 'idtmotivoanulacionpedido';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
