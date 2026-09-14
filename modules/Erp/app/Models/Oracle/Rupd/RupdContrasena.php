<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CONTRASENA
 * Tabla de replicación/materialización de Oracle
 */
class RupdContrasena extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_contrasena';

    protected $primaryKey = 'idcontrasena';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
