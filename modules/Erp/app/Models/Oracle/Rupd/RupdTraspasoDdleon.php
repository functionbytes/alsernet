<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TRASPASO_DDLEON
 * Tabla de replicación/materialización de Oracle
 */
class RupdTraspasoDdleon extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_traspaso_ddleon';

    protected $primaryKey = 'idtraspaso';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
