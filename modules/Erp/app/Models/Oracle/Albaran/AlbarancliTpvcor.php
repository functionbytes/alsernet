<?php

namespace Modules\Erp\Models\Oracle\Albaran;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ALBARANCLI_TPVCOR
 *
 * ÍNDICES DISPONIBLES:
 * ✅ INDX_ALBARANCLI_TPV_IDFACLI (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFACTURACLI
 *
 * PK_ALBARANCLI_TPVCOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALBARANCLI
 */
class AlbarancliTpvcor extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'albarancli_tpvcor';

    protected $primaryKey = 'idalbarancli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcliente', 'idregfiscal', 'idalmacen', 'idseriealbarancli', 'idusuariomod',
        'falbaran', 'nalbarancli', 'idcierre', 'idempleado', 'estado',
        'tipo', 'tentrada', 'observaciones', 'idtipoalbarancli', 'clientetelefono',
        'idenvio', 'nroserie', 'solicita_factura', 'idcatalogo', 'idalbarancli_orig',
        'idregpais', 'idsubc_cli', 'puntosfideliz', 'idfacturacli', 'es_compromiso_alvarez',
        'nfactura_simplificada', 'fenvio_opinion', 'email',
    ];

    protected $casts = [
        'falbaran' => 'datetime',
        'fenvio_opinion' => 'datetime',
        'estado' => 'boolean',
    ];
}
