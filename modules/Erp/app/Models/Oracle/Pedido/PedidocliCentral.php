<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla PEDIDOCLI_CENTRAL
 *
 * @property int $idpedidocli_central Clave primaria (PK)
 * @property int $idpedidocli
 * @property int $idseriepedidocli
 * @property int $idcliente
 * @property int $idalmacen
 * @property int $estado
 *
 * ÍNDICES DISPONIBLES:
 * PK_PEDIDOCLI_CCENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPEDIDOCLI_CENTRAL
 */
class PedidocliCentral extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'pedidocli_central';

    protected $primaryKey = 'idpedidocli_central';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idpedidocli', 'idalmacen', 'idcliente', 'estado', 'fpedido',
        'fcomreserva', 'fliberacion', 'observaciones', 'idusuariomod', 'idregfiscal',
        'idempleado', 'idseriepedidocli', 'idseriepedidocli_central', 'npedidocli', 'tiporiesgo',
        'idprioridad', 'idenvio', 'idorigenpedidocli', 'idusuariocre', 'idusuariobaj',
        'idcatalogo', 'idultimaincidencia', 'fprevista', 'fservido', 'clientetelefono',
        'numeroserie', 'identificadororigen', 'solicitafactura', 'concartuchos', 'servirincompleto',
        'facturado', 'revisadotransp', 'tipopedido', 'idregpais', 'idtmotivoanulacionpedido',
        'idafiliado', 'texto_regalo', 'idclientecuenta', 'idalmacen_creacion', 'es_compromiso_alvarez',
        'idprefijo_telefono',
    ];

    protected $casts = [
        'fpedido' => 'datetime',
        'fcomreserva' => 'datetime',
        'fliberacion' => 'datetime',
        'fprevista' => 'datetime',
        'fservido' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Cliente del pedido
     * ✅ ÓPTIMO: Usa PK_CLIENTE_CENT (ultra rápido)
     */
    public function cliente()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cliente\ClienteCent::class, 'idcliente', 'idcliente');
    }

    /**
     * Almacén del pedido
     * ✅ ÓPTIMO: Usa PK_ALMACEN (ultra rápido)
     */
    public function almacen()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Almacen::class, 'idalmacen', 'idalmacen');
    }

    /**
     * Serie del pedido (Corunya)
     * ✅ ÓPTIMO: Usa PK_SERIEPEDIDOCLI_CORUNYA (ultra rápido)
     */
    public function seriepedidocli()
    {
        return $this->belongsTo(SeriepedidocliCorunya::class, 'idseriepedidocli', 'idseriepedidocli');
    }

    /**
     * Estado del pedido
     * ✅ ÓPTIMO: Usa PK_PEDIDOCLIESTADO (ultra rápido)
     */
    public function estadoInfo()
    {
        return $this->belongsTo(Pedidocliestado::class, 'estado', 'estado');
    }

    /**
     * Líneas del pedido
     * ✅ ÓPTIMO: Usa IDX_LPEDIDOCLI_CENT_PEDCLICENT (indexado)
     * Performance: ~1,147ms para obtener líneas
     */
    public function lineas()
    {
        return $this->hasMany(LpedidocliCentral::class, 'idpedidocli_central', 'idpedidocli_central');
    }

    /**
     * Formas de pago del pedido
     * ✅ ÓPTIMO: Usa IDX_FPPCLI_CENT_IDPEDCLI_CENT (indexado)
     * Performance: ~1.59ms (ultra rápido - 99.9% optimizado)
     */
    public function formasPago()
    {
        return $this->hasMany(\Modules\Erp\Models\Oracle\Cobro\FppedcliCentral::class, 'idpedidocli_central', 'idpedidocli_central');
    }

    /**
     * Relación: Pedido Capthaya (base)
     * ⚠️  SIN ÍNDICE en IDPEDIDOCLI
     */
    public function pedidoCapthaya()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\PedidocliCapthaya::class, 'idpedidocli', 'idpedidocli');
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
     * Relación: Prioridad
     * ⚠️  SIN ÍNDICE en IDPRIORIDAD
     */
    public function prioridad()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Prioridad::class, 'idprioridad', 'idprioridad');
    }

    /**
     * Relación: Origenpedidocli
     * ⚠️  SIN ÍNDICE en IDORIGENPEDIDOCLI
     */
    public function origenpedidocli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\Origenpedidocli::class, 'idorigenpedidocli', 'idorigenpedidocli');
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
     * Relación: Tmotivoanulacionpedido
     * ⚠️  SIN ÍNDICE en IDTMOTIVOANULACIONPEDIDO
     */
    public function tmotivoanulacionpedido()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\Tmotivoanulacionpedido::class, 'idtmotivoanulacionpedido', 'idtmotivoanulacionpedido');
    }

    /**
     * Relación: Afiliado
     * ⚠️  SIN ÍNDICE en IDAFILIADO
     */
    public function afiliado()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Afiliado::class, 'idafiliado', 'idafiliado');
    }

    /**
     * Relación: Clientecuenta
     * ⚠️  SIN ÍNDICE en IDCLIENTECUENTA
     */
    public function clientecuenta()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cliente\ClientecuentaCent::class, 'idclientecuenta', 'idclientecuenta');
    }

    /**
     * Relación: PrefijoTelefono
     * ⚠️  SIN ÍNDICE en IDPREFIJO_TELEFONO
     */
    public function prefijoTelefono()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\PrefijoTelefono::class, 'idprefijo_telefono', 'idprefijo_telefono');
    }
}
