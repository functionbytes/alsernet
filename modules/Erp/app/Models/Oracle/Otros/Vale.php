<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Models\Oracle\Cliente\Cliente;
use Modules\Erp\Models\Oracle\Configuracion\Almacen;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla VALE
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_VALE_IDCLIENTE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTE
 *
 * PK_VALE (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDVALE
 */
class Vale extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'vale';

    protected $primaryKey = 'idvale_anterior';

    public $timestamps = false;

    protected $fillable = [
        'idvale', 'importe', 'fanulacion', 'estado', 'idalmacen',
        'fvalidez', 'tipo', 'idcliente', 'observaciones', 'tiene_codigo_comprobacion',
        'idvale_original',
    ];

    protected $casts = [
        'fanulacion' => 'datetime',
        'fvalidez' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Vale
     * ✅ Usa PK_VALE (indexado)
     */
    public function vale()
    {
        return $this->belongsTo(Vale::class, 'idvale', 'idvale');
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
     * Relación: Cliente
     * ✅ Usa IDX_VALE_IDCLIENTE (indexado)
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idcliente', 'idcliente');
    }
}
