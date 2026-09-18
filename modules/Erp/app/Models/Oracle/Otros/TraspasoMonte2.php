<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Models\Oracle\Configuracion\Almacen;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TRASPASO_MONTE2
 *
 * ÍNDICES DISPONIBLES:
 * PK_TRASPASO_MONTE2 (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTRASPASO
 */
class TraspasoMonte2 extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'traspaso_monte2';

    protected $primaryKey = 'idtraspaso';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    protected $fillable = [
        'idalmacen', 'alm_idalmacen', 'alm_idalmacen2', 'ftraspaso', 'observaciones',
        'estado', 'idtraspasoorig', 'tipo', 'idusuariomod', 'idserietraspaso',
        'ntraspaso', 'idempleado', 'estpowerpick',
    ];

    protected $casts = [
        'ftraspaso' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Traspaso
     * ✅ Usa PK_TRASPASO_MONTE2 (indexado)
     */
    public function traspaso()
    {
        return $this->belongsTo(TraspasoCapthaya::class, 'idtraspaso', 'idtraspaso');
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
