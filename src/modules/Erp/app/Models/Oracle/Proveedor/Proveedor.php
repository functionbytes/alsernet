<?php

namespace Modules\Erp\Models\Oracle\Proveedor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Articulo\Articulo;
use Modules\Erp\Models\Oracle\Configuracion\Banco;
use Modules\Erp\Models\Oracle\Configuracion\Moneda;
use Modules\Erp\Models\Oracle\Configuracion\Pais;
use Modules\Erp\Models\Oracle\Configuracion\Regfiscal;
use Modules\Erp\Models\Oracle\Configuracion\Tipoprov;
use Modules\Erp\Models\Oracle\Otros\Subcuenta;
use Modules\Erp\Traits\UsesOCI8Performance;
use Modules\Supplier\Entities\SupplierErpProvider;

/**
 * Modelo para la tabla PROVEEDOR
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_PROVEEDOR_IDPAIS (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPAIS
 *
 * PK_PROVEEDOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPROVEEDOR
 */
class Proveedor extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'proveedor';

    protected $primaryKey = 'idproveedor';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idtipoprov', 'idbanco', 'nombre', 'cif', 'percontacto',
        'telefono1', 'telefono2', 'fax', 'observaciones', 'web',
        'email', 'portes', 'codcliente', 'estado', 'calle',
        'num', 'localidad', 'cp', 'provincia', 'pais',
        'sucursal_', 'dc_', 'ncuenta_', 'idregfiscal', 'dto',
        'idusuariomod', 'idsubcuenta', 'numart', 'acreedor', 'idmoneda',
        'tposervicio', 'visible', 'nsubcuenta', 'observacionportes', 'iban',
        'idpais', 'dias_servir_parcial', 'porcentaje_servir_parcial',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Pais
     */
    public function pais()
    {
        return $this->belongsTo(Pais::class, 'idpais', 'idpais');
    }

    /**
     * Relación: Proveedor
     * ✅ Usa PK_PROVEEDOR (indexado)
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'idproveedor', 'idproveedor');
    }

    /**
     * Relación: Tipoprov
     * ⚠️  SIN ÍNDICE en IDTIPOPROV
     */
    public function tipoprov()
    {
        return $this->belongsTo(Tipoprov::class, 'idtipoprov', 'idtipoprov');
    }

    /**
     * Relación: Banco
     * ⚠️  SIN ÍNDICE en IDBANCO
     */
    public function banco()
    {
        return $this->belongsTo(Banco::class, 'idbanco', 'idbanco');
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
     * Relación: Subcuenta
     * ⚠️  SIN ÍNDICE en IDSUBCUENTA
     */
    public function subcuenta()
    {
        return $this->belongsTo(Subcuenta::class, 'idsubcuenta', 'idsubcuenta');
    }

    /**
     * Relación: Moneda
     * ⚠️  SIN ÍNDICE en IDMONEDA
     */
    public function moneda()
    {
        return $this->belongsTo(Moneda::class, 'idmoneda', 'idmoneda');
    }

    /**
     * Relación inversa: SupplierErpProviders (sincronización Supplier)
     */
    public function supplierErpProviders(): HasMany
    {
        return $this->hasMany(SupplierErpProvider::class, 'erp_provider_id', 'idproveedor');
    }

    /**
     * Artículos del proveedor (a través de Artiprov)
     */
    public function artiprovs()
    {
        return $this->hasMany(Artiprov::class, 'idproveedor', 'idproveedor');
    }

    /**
     * Artículos del proveedor (relación directa a través de Artiprov)
     */
    public function articulos()
    {
        return $this->hasManyThrough(
            Articulo::class,
            Artiprov::class,
            'idproveedor', // FK en artiprov
            'idarticulo',  // FK en articulo
            'idproveedor', // Local key en proveedor
            'idarticulo'   // Local key en artiprov
        );
    }
}
