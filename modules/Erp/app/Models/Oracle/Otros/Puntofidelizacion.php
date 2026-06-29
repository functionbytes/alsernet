<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Models\Oracle\Albaran\AlbarancliCapthaya;
use Modules\Erp\Models\Oracle\Cliente\Cliente;
use Modules\Erp\Models\Oracle\Configuracion\Almacen;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla PUNTOFIDELIZACION
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_PUNTOFID_IDCLIENTE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTE
 *
 * PK_PUNTOFIDELIZACION (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPUNTOFIDELIZACION
 */
class Puntofidelizacion extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'puntofidelizacion';

    protected $primaryKey = 'idpuntofidelizacion';

    public $timestamps = false;

    protected $fillable = [
        'idtarjeta', 'puntos', 'fecha', 'idalmacen', 'idliquidacion',
        'idalbarancli', 'idcliente', 'estado',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Puntofidelizacion
     * ✅ Usa PK_PUNTOFIDELIZACION (indexado)
     */
    public function puntofidelizacion()
    {
        return $this->belongsTo(Puntofidelizacion::class, 'idpuntofidelizacion', 'idpuntofidelizacion');
    }

    /**
     * Relación: Tarjeta
     * ⚠️  SIN ÍNDICE en IDTARJETA
     */
    public function tarjeta()
    {
        return $this->belongsTo(Tarjetas::class, 'idtarjeta', 'idtarjeta');
    }

    /**
     * Relación: Almacen
     * ⚠️  SIN ÍNDICE en IDALMACEN
     */
    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'idalmacen', 'idalmacen');
    }

    /**
     * Relación: Albarancli
     * ⚠️  SIN ÍNDICE en IDALBARANCLI
     */
    public function albarancli()
    {
        return $this->belongsTo(AlbarancliCapthaya::class, 'idalbarancli', 'idalbarancli');
    }

    /**
     * Relación: Cliente
     * ✅ Usa IDX_PUNTOFID_IDCLIENTE (indexado)
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idcliente', 'idcliente');
    }
}
