<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_FRCAMPO
 * Tabla de replicación/materialización de Oracle
 */
class RupdFrcampo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_frcampo';

    protected $primaryKey = 'cam_idcampo';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'idcampo', 'snapid',
    ];
}
