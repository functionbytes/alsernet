<?php

namespace Modules\Erp\Models\Oracle\Cobro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla FPPEDCLI_TPVCOR
 *
 * ÍNDICES DISPONIBLES:
 * PK_FPPEDCLI_TPVCOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFPPEDCLI
 */
class Fppedcli extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'fppedcli_tpvcor';

    protected $primaryKey = 'idfppedcli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcobrocli', 'idpedidocli', 'idformapago', 'idusuariocre', 'idusuariomod',
        'idusuariobaj', 'estado', 'idclientetarjeta', 'importe', 'not',
        'fautorizacion', 'desc_autorizacion', 'nplazos', 'nsolicitud_vip', 'idvale',
        'pendiente_validacion', 'idusuario_validacion', 'fvalidacion', 'cobro_confirmado', 'cobro_confirmado_fecha',
        'cobro_confirmado_idusuario', 'autorization_id',
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
     * Relación: Fppedcli
     * ✅ Usa PK_FPPEDCLI_TPVCOR (indexado)
     */
    public function fppedcli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\FppedcliCapthaya::class, 'idfppedcli', 'idfppedcli');
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
     * Relación: Pedido
     * ⚠️  SIN ÍNDICE en IDPEDIDOCLI
     */
    public function pedido()
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
