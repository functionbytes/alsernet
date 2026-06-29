<?php

namespace Modules\Erp\Models\Oracle\Cliente;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Pedido\PedidocliCentral;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CLIENTE_CENT
 *
 * @property int $idcliente Clave primaria (PK)
 * @property string $cif
 * @property string $nombre
 * @property string $apellidos
 * @property string $email
 * @property int $idtarjeta
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
class ClienteCent extends Model
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
     * Direcciones del cliente
     */
    public function direcciones()
    {
        return $this->hasMany(ClientedireccionCent::class, 'idcliente', 'idcliente');
    }

    /**
     * Dirección de envío (tipo 1)
     * ✅ Usa FK_CLIENTEDIR_CENT__CLIENTE (indexado en IDCLIENTE)
     * ⚠️  WHERE en IDTIPODIRECCION no usa índice (~90ms)
     */
    public function direccionEnvio()
    {
        return $this->hasOne(ClientedireccionCent::class, 'idcliente', 'idcliente')
            ->where('idtipodireccion', 1);
    }

    /**
     * Pedidos del cliente
     * ⚠️  LENTO: PEDIDOCLI_CENTRAL no tiene índice en IDCLIENTE
     * 💡 Preferir consultar desde PedidocliCentral con WHERE usando IDPEDIDOCLI_CENTRAL
     */
    public function pedidos()
    {
        return $this->hasMany(PedidocliCentral::class, 'idcliente', 'idcliente');
    }

    /**
     * Tarjetas del cliente
     * ✅ Usa FK_CLIENTETAR_CENT__CLIENTE (indexado)
     */
    public function tarjetas()
    {
        return $this->hasMany(ClientetarjetaCent::class, 'idcliente', 'idcliente');
    }

    /**
     * Teléfonos del cliente
     * ✅ Usa FK_CLIENTETEL_CENT__CLIENTE (indexado)
     */
    public function telefonos()
    {
        return $this->hasMany(ClientetelefonoCent::class, 'idcliente', 'idcliente');
    }

    /**
     * Cuentas bancarias del cliente
     * ✅ Usa FK_CLIENTECUENTA_CENT__CLIENTE (indexado)
     */
    public function cuentas()
    {
        return $this->hasMany(ClientecuentaCent::class, 'idcliente', 'idcliente');
    }
}
