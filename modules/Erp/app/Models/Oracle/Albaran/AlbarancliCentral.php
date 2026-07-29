<?php

namespace Modules\Erp\Models\Oracle\Albaran;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ALBARANCLI_CENTRAL
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_ALBCLICENT_IDALMCREACION (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALMACEN_CREACION
 *
 * ✅ IDX_ALBCLICENT_IDFACTURACLI (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFACTURACLI
 *
 * ✅ IDX_ALBCLI_ESTADO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ESTADO
 *
 * ✅ IDX_ALBCLI_FECHA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: FALBARAN
 *
 * ✅ IDX_ALBCLI_IDALM (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALMACEN
 *
 * ✅ IDX_ALBCLI_IDCLIENTE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTE
 *
 * PK_ALBARANCLI_CENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALBARANCLI_CENTRAL
 */
class AlbarancliCentral extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'albarancli_central';

    protected $primaryKey = 'idalbarancli_central';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idalbarancli', 'idcliente', 'idregfiscal', 'idalmacen', 'idseriealbarancli_central',
        'idseriealbarancli', 'idusuariomod', 'falbaran', 'nalbarancli', 'idcierre',
        'idempleado', 'estado', 'tipo', 'tentrada', 'observaciones',
        'idtipoalbarancli', 'clientetelefono', 'idenvio', 'nroserie', 'solicita_factura',
        'idcatalogo', 'idalbarancli_orig', 'idregpais', 'idsubc_cli', 'puntosfideliz',
        'idalmacen_creacion', 'idfacturacli', 'es_compromiso_alvarez', 'nfactura_simplificada', 'fenvio_opinion',
        'email',
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
     * Relación: AlbarancliCentral
     * ✅ Usa PK_ALBARANCLI_CENTRAL (indexado)
     */
    public function albarancliCentral()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\AlbarancliCentral::class, 'idalbarancli_central', 'idalbarancli_central');
    }

    /**
     * Relación: Albarancli
     * ⚠️  SIN ÍNDICE en IDALBARANCLI
     */
    public function albarancli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\AlbarancliCapthaya::class, 'idalbarancli', 'idalbarancli');
    }

    /**
     * Relación: Cliente
     * ✅ Usa IDX_ALBCLI_IDCLIENTE (indexado)
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
     * ✅ Usa IDX_ALBCLI_IDALM (indexado)
     */
    public function almacen()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Almacen::class, 'idalmacen', 'idalmacen');
    }

    /**
     * Relación: SeriealbarancliCentral
     * ⚠️  SIN ÍNDICE en IDSERIEALBARANCLI_CENTRAL
     */
    public function seriealbarancliCentral()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\SeriealbarancliCentral::class, 'idseriealbarancli_central', 'idseriealbarancli_central');
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
     * ✅ Usa IDX_ALBCLICENT_IDFACTURACLI (indexado)
     */
    public function facturacli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Factura\FacturacliCentral::class, 'idfacturacli', 'idfacturacli');
    }
}
