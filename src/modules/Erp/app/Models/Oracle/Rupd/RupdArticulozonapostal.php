<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_ARTICULOZONAPOSTAL
 * Tabla de replicación/materialización de Oracle
 */
class RupdArticulozonapostal extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_articulozonapostal';

    protected $primaryKey = 'idarticulozonapostal';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
