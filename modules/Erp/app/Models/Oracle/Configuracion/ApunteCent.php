<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Otros\AsientoCent;
use Modules\Erp\Models\Oracle\Otros\Empresa;
use Modules\Erp\Models\Oracle\Otros\Subcuenta;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla APUNTE_CENT
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_APUNTE_CENT_ASIENTO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDASIENTO
 *
 * ✅ IDX_APUNTE_CENT_IDSUBC (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSUBCUENTA
 *
 * ✅ IDX_APUNTE_CENT_IDSUBCCONTRA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSUBCUENTACONTRA
 *
 * PK_APUNTE_CENT (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDAPUNTE
 */
class ApunteCent extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'apunte_cent';

    protected $primaryKey = 'idapunte';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idasiento', 'tipodocumento', 'ndocumento', 'idsubcuenta', 'debe',
        'not', 'haber', 'not', 'idusuariocre', 'idusuariomod',
        'idusuariobaj', 'concepto', 'contaplus', 'fapunte', 'fcontaplus',
        'departamento', 'clave', 'baseimp', 'iva', 'recargo',
        'nfactura', 'serie', 'idempresa', 'estado', 'idsubcuentacontra',
        'iva_pc', 'recargo_pc', 'idalmacen', 'idtipo_apunte', 'iddpto_contable',
        'notas', 'cif', 'idtipo_apunte_detalle', 'retencion_pc', 'default',
        'razon_social', 'idproyecto_contable', 'idseccion_contable', 'iddelegacion_contable', 'idcanal_contable',
        'punteado',
    ];

    protected $casts = [
        'fapunte' => 'datetime',
        'fcontaplus' => 'datetime',
        'contaplus' => 'boolean',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Apunte
     * ✅ Usa PK_APUNTE_CENT (indexado)
     */
    public function apunte()
    {
        return $this->belongsTo(ApunteCent::class, 'idapunte', 'idapunte');
    }

    /**
     * Relación: Asiento
     * ✅ Usa IDX_APUNTE_CENT_ASIENTO (indexado)
     */
    public function asiento()
    {
        return $this->belongsTo(AsientoCent::class, 'idasiento', 'idasiento');
    }

    /**
     * Relación: Subcuenta
     * ✅ Usa IDX_APUNTE_CENT_IDSUBC (indexado)
     */
    public function subcuenta()
    {
        return $this->belongsTo(Subcuenta::class, 'idsubcuenta', 'idsubcuenta');
    }

    /**
     * Relación: Empresa
     * ⚠️  SIN ÍNDICE en IDEMPRESA
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'idempresa', 'idempresa');
    }

    /**
     * Relación: Almacen
     * ⚠️  SIN ÍNDICE en IDALMACEN
     */
    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'idalmacen', 'idalmacen');
    }
}
