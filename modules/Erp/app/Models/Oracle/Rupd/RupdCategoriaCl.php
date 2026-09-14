<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CATEGORIA_CL
 * Tabla de replicación/materialización de Oracle
 */
class RupdCategoriaCl extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_categoria_cl';

    protected $primaryKey = 'idcategoria_cl';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
