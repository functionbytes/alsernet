<?php

namespace Modules\Erp\Models\Oracle\Cliente;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CLIENTETARJETA_CENT
 *
 * ÍNDICES DISPONIBLES:
 * ✅ FK_CLIENTETAR_CENT__CLIENTE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTE
 *
 * PK_CLIENTETAR_CENT (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTETARJETA
 */
class ClientetarjetaCent extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'clientetarjeta_cent';

    protected $primaryKey = 'idclientetarjeta';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcliente', 'idtarjeta', 'idusuariocre', 'idusuariobaj', 'idusuariomod',
        'estado', 'numerotarjeta', 'idbanco', 'nombretitular', 'fcaducidad',
        'limite', 'idmoneda', 'observacion', 'cvv',
    ];

    protected $casts = [
        'fcaducidad' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Clientetarjeta
     * ✅ Usa PK_CLIENTETAR_CENT (indexado)
     */
    public function clientetarjeta()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cliente\ClientetarjetaCent::class, 'idclientetarjeta', 'idclientetarjeta');
    }

    /**
     * Relación: Cliente
     * ✅ Usa FK_CLIENTETAR_CENT__CLIENTE (indexado)
     */
    public function cliente()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cliente\Cliente::class, 'idcliente', 'idcliente');
    }

    /**
     * Relación: Tarjeta
     * ⚠️  SIN ÍNDICE en IDTARJETA
     */
    public function tarjeta()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Tarjetas::class, 'idtarjeta', 'idtarjeta');
    }

    /**
     * Relación: Banco
     * ⚠️  SIN ÍNDICE en IDBANCO
     */
    public function banco()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Banco::class, 'idbanco', 'idbanco');
    }

    /**
     * Relación: Moneda
     * ⚠️  SIN ÍNDICE en IDMONEDA
     */
    public function moneda()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Moneda::class, 'idmoneda', 'idmoneda');
    }
}
