<?php

namespace Modules\Erp\Models\Oracle\Albaran;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ALBARANCLI_CORUNYA
 *
 * ÍNDICES DISPONIBLES:
 * ✅ INDX_ALBARANCLI_COR_IDFACLI (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFACTURACLI
 *
 * PK_ALBARANCLI_CORUNYA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALBARANCLI
 */
class AlbarancliCorunya extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'albarancli_corunya';

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

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Albarancli
     * ✅ Usa PK_ALBARANCLI_CORUNYA (indexado)
     */
    public function albarancli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\AlbarancliCapthaya::class, 'idalbarancli', 'idalbarancli');
    }

    /**
     * Relación: Cliente
     * ⚠️  SIN ÍNDICE en IDCLIENTE
     */
    public function cliente()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cliente\Cliente::class, 'idcliente', 'idcliente');
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
     * Relación: Almacen
     * ⚠️  SIN ÍNDICE en IDALMACEN
     */
    public function almacen()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Almacen::class, 'idalmacen', 'idalmacen');
    }

    /**
     * Relación: Seriealbarancli
     * ⚠️  SIN ÍNDICE en IDSERIEALBARANCLI
     */
    public function seriealbarancli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\SeriealbarancliCapthaya::class, 'idseriealbarancli', 'idseriealbarancli');
    }

    /**
     * Relación: Cierre
     * ⚠️  SIN ÍNDICE en IDCIERRE
     */
    public function cierre()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Cierre::class, 'idcierre', 'idcierre');
    }

    /**
     * Relación: Tipoalbarancli
     * ⚠️  SIN ÍNDICE en IDTIPOALBARANCLI
     */
    public function tipoalbarancli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\Tipoalbarancli::class, 'idtipoalbarancli', 'idtipoalbarancli');
    }

    /**
     * Relación: Catalogo
     * ⚠️  SIN ÍNDICE en IDCATALOGO
     */
    public function catalogo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Catalogo\Catalogo::class, 'idcatalogo', 'idcatalogo');
    }

    /**
     * Relación: Regpais
     * ⚠️  SIN ÍNDICE en IDREGPAIS
     */
    public function regpais()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Regpais::class, 'idregpais', 'idregpais');
    }

    /**
     * Relación: Facturacli
     * ✅ Usa INDX_ALBARANCLI_COR_IDFACLI (indexado)
     */
    public function facturacli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Factura\FacturacliCentral::class, 'idfacturacli', 'idfacturacli');
    }
}
