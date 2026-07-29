<?php

namespace Modules\Erp\Models\Oracle\Articulo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ARTICULO (Productos/Artículos)
 *
 * @property int $idarticulo Clave primaria (PK)
 * @property string $codigo Código del artículo
 * @property string $codbar Código de barras
 * @property string $descripcion Descripción del artículo
 * @property int $estado
 * @property int $idmarca
 * @property int $idmodelo
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_ARTICULO_CALIBRE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCALIBRE
 *
 * ✅ IDX_ARTICULO_CODBAR (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: CODBAR
 *
 * ✅ IDX_ARTICULO_CODIGO_UPPER (NONUNIQUE)
 *    - Tipo: FUNCTION-BASED NORMAL
 *    - Columnas: SYS_NC00065$
 *
 * ✅ IDX_ARTICULO_COMPRACENT (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: COMPRA_CENTRALIZADA
 *
 * ✅ IDX_ARTICULO_DESCRIPCION (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: DESCRIPCION
 *
 * ✅ IDX_ARTICULO_DESCRIPCION_UPPER (NONUNIQUE)
 *    - Tipo: FUNCTION-BASED NORMAL
 *    - Columnas: SYS_NC00066$
 *
 * ✅ IDX_ARTICULO_IDGRUPO_CL (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDGRUPO_CL
 *
 * ✅ IDX_ARTICULO_IDMARCA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDMARCA
 *
 * ✅ IDX_ARTICULO_IDMODELO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDMODELO
 *
 * ✅ IDX_ARTICULO_IDSUBCUENTACOMPRA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSUBCUENTACOMPRA
 *
 * ✅ IDX_ARTICULO_IDSUBCUENTAVENTA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSUBCUENTAVENTA
 *
 * ✅ IDX_ARTICULO_IDTIPOMEDIDA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTIPOMEDIDA
 *
 * ✅ IDX_ARTICULO_IDTIPOUNIDADES (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTIPOUNIDADES
 *
 * ✅ INDX_ARTICULO_ESTADO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ESTADO
 *
 * ✅ INDX_ARTICULO_IDLOTE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLOTE
 *
 * PK_ARTICULO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTICULO
 *
 * ✅ RELATION_13_FK (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTIPOART
 *
 * ✅ RELATION_1672_FK (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDIMPUESTO
 *
 * ✅ RELATION_8_FK (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSUBFAMILIA
 *
 * ✅ UK_ARTICULO_EAN_INTERNO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: EAN_INTERNO
 *
 * ✅ UNIQUE_CODIGO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: CODIGO
 */
class Articulo extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'articulo';

    protected $primaryKey = 'idarticulo';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idimpuesto', 'idsubfamilia', 'idtipoart', 'codigo', 'codbar',
        'descripcion', 'estado', 'maxservir', 'preciomedio', 'precioultcompra',
        'idusuariomod', 'rutaimagen', 'largo', 'ancho', 'alto',
        'palet', 'maxaltura', 'idtipounidades', 'idtipomedida', 'idsubcuentacompra',
        'idsubcuentaventa', 'codpro', 'deposito', 'conversion', 'precioabierto',
        'idmarca', 'codcorto', 'idlote', 'fdisponibilidad', 'enviarptosinternet',
        'observaciones', 'compra_centralizada', 'idgrupo_cl', 'permitir_reservar', 'fprecioultcompra',
        'idcalibre', 'unidadesporcaja', 'preciorecomendadoprov', 'fbajostockacero', 'segundamano',
        'nserie', 'sinplazoproveedor', 'idperiodo_cuota', 'pedir_numero_serie', 'no_apunte_iva_0',
        'es_loteria', 'observaciones_compras', 'ean_interno', 'externo', 'externo_disponibilidad',
        'intrastat', 'peso', 'idmodelo', 'kardex_actualizado', 'disponibilidad_automatica',
        'es_csw', 'idtipoarma', 'iddiseno_etiq_electronica', 'estado_publicado_web', 'referencia',
    ];

    protected $casts = [
        'fdisponibilidad' => 'datetime',
        'fprecioultcompra' => 'datetime',
        'fbajostockacero' => 'datetime',
        'estado' => 'boolean',
        'estado_publicado_web' => 'boolean',
    ];

    // ========================================
    // Relaciones Optimizadas
    // ========================================

    /**
     * Stock del artículo en almacenes
     * ✅ Usa IDX_STOCK_CENTRAL_IDART (indexado en IDARTICULO)
     */
    public function stocks()
    {
        return $this->hasMany(\Modules\Erp\Models\Oracle\Otros\StockCentral::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Líneas de pedidos de cliente
     * ⚠️  LENTO: LPEDIDOCLI_CENTRAL no tiene índice en IDARTICULO
     * 💡 Evitar esta relación para consultas masivas
     */
    public function lineasPedido()
    {
        return $this->hasMany(\Modules\Erp\Models\Oracle\Pedido\LpedidocliCentral::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Marca del artículo
     * ✅ Relación inversa optimizada (Marca tiene PK)
     */
    public function marca()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Marca::class, 'idmarca', 'idmarca');
    }

    /**
     * Modelo del artículo
     * ✅ Relación inversa optimizada (Modelo tiene PK)
     */
    public function modelo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Catalogo\Modelo::class, 'idmodelo', 'idmodelo');
    }

    /**
     * Subfamilia del artículo
     * ✅ Usa PK de subfamilia
     */
    public function subfamilia()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\SubfamiliaCl::class, 'idsubfamilia', 'idsubfamilia_cl');
    }

    /**
     * Tipo de artículo
     * ✅ Usa PK de tipoart
     */
    public function tipoArticulo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Tipoart::class, 'idtipoart', 'idtipoart');
    }

    /**
     * Relación: Articulo
     * ✅ Usa PK_ARTICULO (indexado)
     */
    public function articulo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Articulo\Articulo::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Relación: Impuesto
     * ✅ Usa RELATION_1672_FK (indexado)
     */
    public function impuesto()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Impuesto::class, 'idimpuesto', 'idimpuesto');
    }

    /**
     * Relación: Tipoart
     * ✅ Usa RELATION_13_FK (indexado)
     */
    public function tipoart()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Tipoart::class, 'idtipoart', 'idtipoart');
    }

    /**
     * Relación: Tipounidades
     * ✅ Usa IDX_ARTICULO_IDTIPOUNIDADES (indexado)
     */
    public function tipounidades()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Tipounidades::class, 'idtipounidades', 'idtipounidades');
    }

    /**
     * Relación: Tipomedida
     * ✅ Usa IDX_ARTICULO_IDTIPOMEDIDA (indexado)
     */
    public function tipomedida()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Tipomedida::class, 'idtipomedida', 'idtipomedida');
    }

    /**
     * Relación: Lote
     * ✅ Usa INDX_ARTICULO_IDLOTE (indexado)
     */
    public function lote()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Lote\Lote::class, 'idlote', 'idlote');
    }

    /**
     * Relación: GrupoCl
     * ✅ Usa IDX_ARTICULO_IDGRUPO_CL (indexado)
     */
    public function grupoCl()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\GrupoCl::class, 'idgrupo_cl', 'idgrupo_cl');
    }

    /**
     * Relación: Calibre
     * ✅ Usa IDX_ARTICULO_CALIBRE (indexado)
     */
    public function calibre()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Calibre::class, 'idcalibre', 'idcalibre');
    }

    /**
     * Relación: PeriodoCuota
     * ⚠️  SIN ÍNDICE en IDPERIODO_CUOTA
     */
    public function periodoCuota()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\PeriodoCuota::class, 'idperiodo_cuota', 'idperiodo_cuota');
    }

    /**
     * Relación inversa: SupplierProducts (sincronización Supplier)
     */
    public function supplierProducts(): HasMany
    {
        return $this->hasMany(\Modules\Supplier\Entities\SupplierProduct::class, 'erp_product_id', 'idarticulo');
    }

    /**
     * Tarifas cabecera del artículo
     * ✅ Usa IDX_TARIFA_CABECERA_IDARTICULO (indexado)
     */
    public function tarifasCabecera(): HasMany
    {
        return $this->hasMany(\Modules\Erp\Models\Oracle\Otros\TarifaCabecera::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Perfiles de producto web (valores de características para este artículo)
     * ✅ Usa IDX_WPERFILES_PRO_WPRODUCTO (indexado en IDARTICULO)
     */
    public function perfilesProd(): HasMany
    {
        return $this->hasMany(\Modules\Erp\Models\Oracle\Web\WPerfilesProd::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Columnas optimizadas para fastPaginate()
     */
    protected static function getCustomSelectColumns(): string
    {
        return 't.idarticulo, t.codigo, t.codbar, t.descripcion, '.
               't.preciomedio, t.precioultcompra, t.idmarca, t.idmodelo, '.
               't.estado, t.fcreacion, t.fmodificacion';
    }
}
