<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_SUBFAMILIA_CL
 * Tabla de replicación/materialización de Oracle
 */
class RupdSubfamiliaCl extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_subfamilia_cl';

    protected $primaryKey = 'idsubfamilia_cl';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
