<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPROMOCIONORIGENEXCL
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpromocionorigenexcl extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpromocionorigenexcl';

    protected $primaryKey = 'idlpromocionorigenexcluido';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
