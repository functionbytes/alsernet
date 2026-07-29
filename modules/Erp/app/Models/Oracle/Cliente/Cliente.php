<?php

namespace Modules\Erp\Models\Oracle\Cliente;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Promocion\LgeneracionBonoPromocion;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CLIENTE_CENT
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_CLIENTECENT_FOTOFIRMALOPD (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFOTOGRAFIA_FIRMA_LOPD
 *
 * ✅ IDX_CLIENTE_CENT_CIF (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: CIF
 *
 * ✅ IDX_CLIENTE_CENT_CIF_UPPER (NONUNIQUE)
 *    - Tipo: FUNCTION-BASED NORMAL
 *    - Columnas: SYS_NC00051$
 *
 * ✅ IDX_CLIENTE_CENT_CODIGO_INT (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: CODIGO_INTERNET
 *
 * ✅ IDX_CLIENTE_CENT_IDTARJ (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTARJETA
 *
 * PK_CLIENTE_CENT (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTE
 */
class Cliente extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'cliente_cent';

    protected $primaryKey = 'idcliente';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idtipocliente', 'idregfiscal', 'estado', 'nombre', 'cif',
        'email', 'percontacto', 'razonsocial', 'idusuariomod', 'dto',
        'idttarifa', 'riesgo', 'diasreserva', 'idsubcuenta', 'riesgomaximo',
        'idformapago', 'idbanco_', 'observaciones', 'nsuplemento', 'diapago',
        'sucursal_', 'dc_', 'ncuenta_', 'iddireccion', 'iddireccion_notif',
        'idpaisnacionalidad', 'ididioma', 'deudor', 'busquedanombre', 'idregpais',
        'suscripbonos', 'idtarjeta', 'fasignacion_tarjeta', 'idalmacen_asignacion_tarjeta', 'idcliente_original',
        'ventamayor', 'idcategoria_cliente', 'codigo_internet', 'fnacimiento', 'genero',
        'oficina_contable', 'organo_gestor', 'unidad_tramitadora', 'categoria_cambio_fecha', 'categoria_cambio_motivo',
        'categoria_fecha_prox_revision', 'faceptacion_lopd', 'idusuario_aceptacion_lopd', 'origen_aceptacion_lopd', 'idfotografia_firma_lopd',
        'ubicacion_aceptacion_lopd', 'no_informacion_comercial_lopd', 'no_datos_a_terceros_lopd', 'tiene_interes_legitimo_lopd', 'idusuariocre',
        'organo_proponente', 'apellidos',
    ];

    protected $casts = [
        'fasignacion_tarjeta' => 'datetime',
        'fnacimiento' => 'datetime',
        'categoria_cambio_fecha' => 'datetime',
        'categoria_fecha_prox_revision' => 'datetime',
        'faceptacion_lopd' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con ClienteSeguro
     */
    public function clienteSeguros()
    {
        return $this->hasMany(ClienteSeguro::class, 'idcliente', 'idcliente_cent');
    }

    /**
     * Relación inversa con LgeneracionBonoPromocion
     */
    public function lgeneracionBonoPromocions()
    {
        return $this->hasMany(LgeneracionBonoPromocion::class, 'idcliente', 'idcliente_cent');
    }

    /**
     * Relación: Cliente
     * ✅ Usa PK_CLIENTE_CENT (indexado)
     */
    public function cliente()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cliente\Cliente::class, 'idcliente', 'idcliente');
    }

    /**
     * Relación: Tipocliente
     * ⚠️  SIN ÍNDICE en IDTIPOCLIENTE
     */
    public function tipocliente()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Tipocliente::class, 'idtipocliente', 'idtipocliente');
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
     * Relación: Ttarifa
     * ⚠️  SIN ÍNDICE en IDTTARIFA
     */
    public function ttarifa()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Ttarifa::class, 'idttarifa', 'idttarifa');
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
     * Relación: Formapago
     * ⚠️  SIN ÍNDICE en IDFORMAPAGO
     */
    public function formapago()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\Formapago::class, 'idformapago', 'idformapago');
    }

    /**
     * Relación: Idioma
     * ⚠️  SIN ÍNDICE en IDIDIOMA
     */
    public function idioma()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Idioma::class, 'ididioma', 'ididioma');
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
     * Relación: Tarjeta
     * ✅ Usa IDX_CLIENTE_CENT_IDTARJ (indexado)
     */
    public function tarjeta()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Tarjetas::class, 'idtarjeta', 'idtarjeta');
    }

    /**
     * Relación: CategoriaCliente
     * ⚠️  SIN ÍNDICE en IDCATEGORIA_CLIENTE
     */
    public function categoriaCliente()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\CategoriaCliente::class, 'idcategoria_cliente', 'idcategoria_cliente');
    }

    /**
     * Pedidos del cliente
     * ✅ Usa índice en IDCLIENTE
     */
    public function pedidos()
    {
        return $this->hasMany(\Modules\Erp\Models\Oracle\Pedido\PedidocliCentral::class, 'idcliente', 'idcliente');
    }

    /**
     * Albaranes del cliente
     * ✅ Usa índice en IDCLIENTE
     */
    public function albaranes()
    {
        return $this->hasMany(\Modules\Erp\Models\Oracle\Albaran\AlbarancliCentral::class, 'idcliente', 'idcliente');
    }

    /**
     * Bonos del cliente
     * ✅ Usa IDX_BONO_PROMO_IDCLIENTE
     */
    public function bonos()
    {
        return $this->hasMany(\Modules\Erp\Models\Oracle\Promocion\BonoPromocion::class, 'idcliente', 'idcliente');
    }

    /**
     * Vales del cliente
     * ✅ Usa IDX_VALE_IDCLIENTE
     */
    public function vales()
    {
        return $this->hasMany(\Modules\Erp\Models\Oracle\Otros\Vale::class, 'idcliente', 'idcliente');
    }

    /**
     * Catálogos del cliente
     */
    public function catalogos()
    {
        return $this->hasMany(\Modules\Erp\Models\Oracle\Cliente\ClientecatalogoCent::class, 'idcliente', 'idcliente');
    }
}
