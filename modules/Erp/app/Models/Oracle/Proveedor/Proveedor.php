<?php

namespace Modules\Erp\Models\Oracle\Proveedor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Configuracion\Pais;
use Modules\Erp\Traits\UsesOCI8Performance;

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
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Proveedor\Proveedor::class, 'idproveedor', 'idproveedor');
    }

    /**
     * Relación: Tipoprov
     * ⚠️  SIN ÍNDICE en IDTIPOPROV
     */
    public function tipoprov()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Tipoprov::class, 'idtipoprov', 'idtipoprov');
    }

    /**
     * Relación: Banco
     * ⚠️  SIN ÍNDICE en IDBANCO
     */
    public function banco()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Banco::class, 'idbanco', 'idbanco');
    }

    /**
     * Relación: Regfiscal
     * ⚠️  SIN ÍNDICE en IDREGFISCAL
     */
    public function regfiscal()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Regfiscal::class, 'idregfiscal', 'idregfiscal');
    }

    /**
     * Relación: Subcuenta
     * ⚠️  SIN ÍNDICE en IDSUBCUENTA
     */
    public function subcuenta()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Subcuenta::class, 'idsubcuenta', 'idsubcuenta');
    }

    /**
     * Relación: Moneda
     * ⚠️  SIN ÍNDICE en IDMONEDA
     */
    public function moneda()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Moneda::class, 'idmoneda', 'idmoneda');
    }

    /**
     * Relación inversa: SupplierErpProviders (sincronización Supplier)
     */
    public function supplierErpProviders(): HasMany
    {
        return $this->hasMany(\Modules\Supplier\Entities\SupplierErpProvider::class, 'erp_provider_id', 'idproveedor');
    }

    /**
     * Artículos del proveedor (a través de Artiprov)
     */
    public function artiprovs()
    {
        return $this->hasMany(\Modules\Erp\Models\Oracle\Proveedor\Artiprov::class, 'idproveedor', 'idproveedor');
    }

    /**
     * Artículos del proveedor (relación directa a través de Artiprov)
     */
    public function articulos()
    {
        return $this->hasManyThrough(
            \Modules\Erp\Models\Oracle\Articulo\Articulo::class,
            \Modules\Erp\Models\Oracle\Proveedor\Artiprov::class,
            'idproveedor', // FK en artiprov
            'idarticulo',  // FK en articulo
            'idproveedor', // Local key en proveedor
            'idarticulo'   // Local key en artiprov
        );
    }
}
