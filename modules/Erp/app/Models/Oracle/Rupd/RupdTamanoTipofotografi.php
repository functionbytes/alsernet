<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TAMANO_TIPOFOTOGRAFI
 * Tabla de replicación/materialización de Oracle
 */
class RupdTamanoTipofotografi extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tamano_tipofotografi';

    protected $primaryKey = 'idtamano_tipofotografia';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
