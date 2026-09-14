<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_W_MODELO_DOCUMENTOS_
 * Tabla de replicación/materialización de Oracle
 */
class RupdWModeloDocumentos extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_w_modelo_documentos_';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
