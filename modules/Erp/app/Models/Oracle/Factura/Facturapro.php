<?php

namespace Modules\Erp\Models\Oracle\Factura;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Models\Oracle\Catalogo\Catalogo;
use Modules\Erp\Models\Oracle\Cobro\Formapago;
use Modules\Erp\Models\Oracle\Configuracion\Almacen;
use Modules\Erp\Models\Oracle\Configuracion\Regfiscal;
use Modules\Erp\Models\Oracle\Otros\AsientoCent;
use Modules\Erp\Models\Oracle\Otros\Condicionpago;
use Modules\Erp\Models\Oracle\Proveedor\Deudapro;
use Modules\Erp\Models\Oracle\Proveedor\Proveedor;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla FACTURAPRO
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_FACTURAPRO_IDDEUDAPRO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDDEUDAPRO
 *
 * ✅ IDX_FACTURAPRO_IDFACTURAPRO_RE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFACTURAPRO_RECTIFICADA
 *
 * ✅ IDX_FACTURAPRO_IDSERIEFACTURAP (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSERIEFACTURAPRO
 *
 * PK_FACTURAPRO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFACTURAPRO
 */
class Facturapro extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'facturapro';

    protected $primaryKey = 'idfacturapro_rectificada';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    protected $fillable = [
        'idfacturapro', 'idtestfactura', 'idproveedor', 'idregfiscal', 'estado',
        'ffactura', 'nfacprov', 'idempleado', 'idusuariomod', 'idseriefacturapro',
        'nfactura', 'dto', 'iddeudapro', 'idasiento', 'tipo',
        'idcatalogo', 'idformapago', 'idcondicionpago', 'idalmacen', 'portes',
        'not', 'aduana', 'not', 'idcaja', 'foperacion',
        'fregistro',
    ];

    protected $casts = [
        'ffactura' => 'datetime',
        'foperacion' => 'datetime',
        'fregistro' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Facturapro
     */
    public function facturapro_rectificada()
    {
        return $this->belongsTo(Facturapro::class, 'idfacturapro_rectificada', 'idfacturapro');
    }

    /**
     * Relación con Seriefacturapro
     */
    public function seriefacturapro()
    {
        return $this->belongsTo(Seriefacturapro::class, 'idseriefacturapro', 'idseriefacturapro');
    }

    /**
     * Relación con Deudapro
     */
    public function deudapro()
    {
        return $this->belongsTo(Deudapro::class, 'iddeudapro', 'iddeudapro');
    }

    /**
     * Relación inversa con Facturapro
     */
    public function facturapros()
    {
        return $this->hasMany(Facturapro::class, 'idfacturapro_rectificada', 'idfacturapro');
    }

    /**
     * Relación: FacturaproRectificada
     * ✅ Usa IDX_FACTURAPRO_IDFACTURAPRO_RE (indexado)
     */
    public function facturaproRectificada()
    {
        return $this->belongsTo(Facturapro::class, 'idfacturapro_rectificada', 'idfacturapro');
    }

    /**
     * Relación: Facturapro
     * ✅ Usa PK_FACTURAPRO (indexado)
     */
    public function facturapro()
    {
        return $this->belongsTo(Facturapro::class, 'idfacturapro', 'idfacturapro');
    }

    /**
     * Relación: Testfactura
     * ⚠️  SIN ÍNDICE en IDTESTFACTURA
     */
    public function testfactura()
    {
        return $this->belongsTo(Testfactura::class, 'idtestfactura', 'idtestfactura');
    }

    /**
     * Relación: Proveedor
     * ⚠️  SIN ÍNDICE en IDPROVEEDOR
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'idproveedor', 'idproveedor');
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
     * Relación: Asiento
     * ⚠️  SIN ÍNDICE en IDASIENTO
     */
    public function asiento()
    {
        return $this->belongsTo(AsientoCent::class, 'idasiento', 'idasiento');
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
     * Relación: Formapago
     * ⚠️  SIN ÍNDICE en IDFORMAPAGO
     */
    public function formapago()
    {
        return $this->belongsTo(Formapago::class, 'idformapago', 'idformapago');
    }

    /**
     * Relación: Condicionpago
     * ⚠️  SIN ÍNDICE en IDCONDICIONPAGO
     */
    public function condicionpago()
    {
        return $this->belongsTo(Condicionpago::class, 'idcondicionpago', 'idcondicionpago');
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
