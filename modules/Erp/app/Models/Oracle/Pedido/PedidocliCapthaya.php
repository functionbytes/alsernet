<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla PEDIDOCLI_CAPTHAYA
 *
 * ÍNDICES DISPONIBLES:
 * PK_PEDIDOCLI_CAPTHAYA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPEDIDOCLI
 */
class PedidocliCapthaya extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'pedidocli_capthaya';

    protected $primaryKey = 'idpedidocli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idalmacen', 'idcliente', 'estado', 'fpedido', 'fcomreserva',
        'fliberacion', 'observaciones', 'idusuariomod', 'idregfiscal', 'idempleado',
        'idseriepedidocli', 'npedidocli', 'tiporiesgo', 'idprioridad', 'idenvio',
        'idorigenpedidocli', 'idusuariocre', 'idusuariobaj', 'idcatalogo', 'idultimaincidencia',
        'fprevista', 'fservido', 'clientetelefono', 'numeroserie', 'identificadororigen',
        'solicitafactura', 'concartuchos', 'servirincompleto', 'facturado', 'revisadotransp',
        'tipopedido', 'idregpais', 'idtmotivoanulacionpedido', 'idafiliado', 'texto_regalo',
        'idclientecuenta', 'es_compromiso_alvarez', 'idprefijo_telefono',
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
     * Relación: Pedido
     * ✅ Usa PK_PEDIDOCLI_CAPTHAYA (indexado)
     */
    public function pedido()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\PedidocliCapthaya::class, 'idpedidocli', 'idpedidocli');
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
     * Relación: Seriepedidocli
     * ⚠️  SIN ÍNDICE en IDSERIEPEDIDOCLI
     */
    public function seriepedidocli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\SeriepedidocliCapthaya::class, 'idseriepedidocli', 'idseriepedidocli');
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
