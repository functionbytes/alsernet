<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_PREFIJO_TELEFONO
 * Tabla de replicación/materialización de Oracle
 */
class RupdPrefijoTelefono extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_prefijo_telefono';

    protected $primaryKey = 'idprefijo_telefono';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
