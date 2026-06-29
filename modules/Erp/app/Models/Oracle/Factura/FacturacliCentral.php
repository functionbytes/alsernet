<?php

namespace Modules\Erp\Models\Oracle\Factura;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Catalogo\Catalogo;
use Modules\Erp\Models\Oracle\Cliente\Cliente;
use Modules\Erp\Models\Oracle\Cobro\Formapago;
use Modules\Erp\Models\Oracle\Configuracion\Almacen;
use Modules\Erp\Models\Oracle\Configuracion\Pais;
use Modules\Erp\Models\Oracle\Configuracion\Regfiscal;
use Modules\Erp\Models\Oracle\Configuracion\Regpais;
use Modules\Erp\Models\Oracle\Otros\AsientoCent;
use Modules\Erp\Models\Oracle\Serie\Serie;
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
class FacturacliCentral extends Model
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

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Facturacli
     * ✅ Usa INDX_FACTURACLI_IDFACTURACLI (indexado)
     */
    public function facturacli()
    {
        return $this->belongsTo(FacturacliCentral::class, 'idfacturacli', 'idfacturacli');
    }

    /**
     * Relación: Cliente
     * ⚠️  SIN ÍNDICE en IDCLIENTE
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idcliente', 'idcliente');
    }

    /**
     * Relación: Regfiscal
     * ⚠️  SIN ÍNDICE en IDREGFISCAL
     */
    public function regfiscal()
    {
        return $this->belongsTo(Regfiscal::class, 'idregfiscal', 'idregfiscal');
    }

    /**
     * Relación: Serie
     * ⚠️  SIN ÍNDICE en IDSERIE
     */
    public function serie()
    {
        return $this->belongsTo(Serie::class, 'idserie', 'idserie');
    }

    /**
     * Relación: Asiento
     * ✅ Usa IDX_FACTURACLI_IDASIENTO (indexado)
     */
    public function asiento()
    {
        return $this->belongsTo(AsientoCent::class, 'idasiento', 'idasiento');
    }

    /**
     * Relación: Pais
     * ⚠️  SIN ÍNDICE en IDPAIS
     */
    public function pais()
    {
        return $this->belongsTo(Pais::class, 'idpais', 'idpais');
    }

    /**
     * Relación: Formapago
     * ⚠️  SIN ÍNDICE en IDFORMAPAGO
     */
    public function formapago()
    {
        return $this->belongsTo(Formapago::class, 'idformapago', 'idformapago');
    }

    /**
     * Relación: Catalogo
     * ⚠️  SIN ÍNDICE en IDCATALOGO
     */
    public function catalogo()
    {
        return $this->belongsTo(Catalogo::class, 'idcatalogo', 'idcatalogo');
    }

    /**
     * Relación: Regpais
     * ⚠️  SIN ÍNDICE en IDREGPAIS
     */
    public function regpais()
    {
        return $this->belongsTo(Regpais::class, 'idregpais', 'idregpais');
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
