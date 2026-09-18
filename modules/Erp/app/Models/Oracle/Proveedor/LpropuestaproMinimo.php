<?php

namespace Modules\Erp\Models\Oracle\Proveedor;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Models\Oracle\Configuracion\Almacen;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LPROPUESTAPRO_MINIMO
 *
 * ÍNDICES DISPONIBLES:
 * PK_LPROPUESTAPRO_MINIMO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLPROPUESTAPRO_MINIMO
 */
class LpropuestaproMinimo extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lpropuestapro_minimo';

    protected $primaryKey = 'idlpropuestapro_minimo';

    public $timestamps = false;

    protected $fillable = [
        'idlpropuestapro', 'idalmacen', 'minimo_original', 'maximo_original', 'recomendado_original',
        'minimo', 'maximo', 'recomendado',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: LpropuestaproMinimo
     * ✅ Usa PK_LPROPUESTAPRO_MINIMO (indexado)
     */
    public function lpropuestaproMinimo()
    {
        return $this->belongsTo(LpropuestaproMinimo::class, 'idlpropuestapro_minimo', 'idlpropuestapro_minimo');
    }

    /**
     * Relación: Lpropuestapro
     * ⚠️  SIN ÍNDICE en IDLPROPUESTAPRO
     */
    public function lpropuestapro()
    {
        return $this->belongsTo(Lpropuestapro::class, 'idlpropuestapro', 'idlpropuestapro');
    }

    /**
     * Relación: Almacen
     * ⚠️  SIN ÍNDICE en IDALMACEN
     */
    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'idalmacen', 'idalmacen');
    }
}
