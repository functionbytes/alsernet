<?php

namespace Modules\Erp\Models\Oracle\Cliente;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CLIENTECUOTA
 *
 * ÍNDICES DISPONIBLES:
 * PK_CLIENTECUOTA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTECUOTA
 */
class Clientecuota extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'clientecuota';

    protected $primaryKey = 'idclientecuota';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcliente', 'idlpedidocli', 'idalmacen', 'idarticulo', 'fcontratacion',
        'ffinservicio', 'importe', 'not', 'estado', 'idusuariocre',
        'idusuariomod', 'idusuariobaj', 'idclientecuenta',
    ];

    protected $casts = [
        'fcontratacion' => 'datetime',
        'ffinservicio' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Clientecuota
     * ✅ Usa PK_CLIENTECUOTA (indexado)
     */
    public function clientecuota()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cliente\Clientecuota::class, 'idclientecuota', 'idclientecuota');
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
     * Relación: Lpedidocli
     * ⚠️  SIN ÍNDICE en IDLPEDIDOCLI
     */
    public function lpedidocli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\LpedidocliCapthaya::class, 'idlpedidocli', 'idlpedidocli');
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
     * Relación: Articulo
     * ⚠️  SIN ÍNDICE en IDARTICULO
     */
    public function articulo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Articulo\Articulo::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Relación: Clientecuenta
     * ⚠️  SIN ÍNDICE en IDCLIENTECUENTA
     */
    public function clientecuenta()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cliente\ClientecuentaCent::class, 'idclientecuenta', 'idclientecuenta');
    }
}
