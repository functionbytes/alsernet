<?php

namespace Modules\Erp\Models\Oracle\Factura;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla FACTURACLI_CENTRAL
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_FACTURACLI_IDASIENTO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDASIENTO
 *
 * ✅ INDX_FACTURACLI_IDFACTURACLI (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFACTURACLI
 */
class Facturacli extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'facturacli_central';

    protected $primaryKey = 'idfacturacli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcliente', 'iddeuda', 'idregfiscal', 'idserie', 'nfactura',
        'anno', 'nombre', 'cif', 'calle', 'numero',
        'localidad', 'cp', 'provincia', 'pais', 'ffactura',
        'idusuariomod', 'nombre_emp', 'cif_emp', 'calle_emp', 'numero_emp',
        'localidad_emp', 'cp_emp', 'provincia_emp', 'pais_emp', 'dto',
        'not', 'idempleado', 'observaciones', 'dto2', 'idasiento',
        'idpais', 'tipo', 'idformapago', 'estado', 'idsubcta_cli',
        'pasar_a_conta', 'idcatalogo', 'idsubcta_venta', 'idregpais', 'idalmacen',
        'oficina_contable', 'organo_gestor', 'unidad_tramitadora', 'idfacturacli_rectificada', 'simplificada',
        'organo_proponente',
    ];

    protected $casts = [
        'ffactura' => 'datetime',
        'estado' => 'boolean',
    ];
}
