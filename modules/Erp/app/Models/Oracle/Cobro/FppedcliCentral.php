<?php

namespace Modules\Erp\Models\Oracle\Cobro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla FPPEDCLI_CENTRAL (Formas de Pago de Pedidos)
 *
 * @property int $idfppedcli_central Clave primaria (PK)
 * @property int $idpedidocli_central Foreign key a PEDIDOCLI_CENTRAL
 * @property int $idformapago
 * @property float $importe
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_FPPCLI_CENT_IDPEDCLI_CENT (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPEDIDOCLI_CENTRAL
 *
 * PK_FPPEDCLI_CENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFPPEDCLI_CENTRAL
 */
class FppedcliCentral extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'fppedcli_central';

    protected $primaryKey = 'idfppedcli_central';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idfppedcli', 'idcobrocli_central', 'idcobrocli', 'idpedidocli_central', 'idpedidocli',
        'idformapago', 'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado',
        'idclientetarjeta', 'importe', 'not', 'fautorizacion', 'desc_autorizacion',
        'idalmacen_creacion', 'idvale', 'pendiente_validacion', 'idusuario_validacion', 'fvalidacion',
        'cobro_confirmado', 'cobro_confirmado_fecha', 'cobro_confirmado_idusuario', 'autorization_id',
    ];

    protected $casts = [
        'fautorizacion' => 'datetime',
        'fvalidacion' => 'datetime',
        'cobro_confirmado_fecha' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: FppedcliCentral
     * ✅ Usa PK_FPPEDCLI_CENTRAL (indexado)
     */
    public function fppedcliCentral()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\FppedcliCentral::class, 'idfppedcli_central', 'idfppedcli_central');
    }

    /**
     * Relación: Fppedcli
     * ⚠️  SIN ÍNDICE en IDFPPEDCLI
     */
    public function fppedcli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\FppedcliCapthaya::class, 'idfppedcli', 'idfppedcli');
    }

    /**
     * Relación: CobrocliCentral
     * ⚠️  SIN ÍNDICE en IDCOBROCLI_CENTRAL
     */
    public function cobrocliCentral()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\CobrocliCentral::class, 'idcobrocli_central', 'idcobrocli_central');
    }

    /**
     * Relación: Cobrocli
     * ⚠️  SIN ÍNDICE en IDCOBROCLI
     */
    public function cobrocli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\CobrocliCapthaya::class, 'idcobrocli', 'idcobrocli');
    }

    /**
     * Pedido Central (optimizado)
     * ✅ Usa IDX_FPPCLI_CENT_IDPEDCLI_CENT (indexado)
     */
    public function pedido()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\PedidocliCentral::class, 'idpedidocli_central', 'idpedidocli_central');
    }

    /**
     * Pedido Capthaya (base)
     * ⚠️  SIN ÍNDICE en IDPEDIDOCLI
     */
    public function pedidoCapthaya()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\PedidocliCapthaya::class, 'idpedidocli', 'idpedidocli');
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
     * Relación: Clientetarjeta
     * ⚠️  SIN ÍNDICE en IDCLIENTETARJETA
     */
    public function clientetarjeta()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cliente\ClientetarjetaCent::class, 'idclientetarjeta', 'idclientetarjeta');
    }

    /**
     * Relación: Vale
     * ⚠️  SIN ÍNDICE en IDVALE
     */
    public function vale()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Vale::class, 'idvale', 'idvale');
    }
}
