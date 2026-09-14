<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_W_PORTES_TIPO
 * Tabla de replicación/materialización de Oracle
 */
class RupdWPortesTipo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_w_portes_tipo';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
