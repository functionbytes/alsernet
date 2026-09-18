<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla DESTINOCAMBIO
 *
 * ÍNDICES DISPONIBLES:
 * PK_DESTINOCAMBIO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDDESTINOCAMBIO
 */
class Destinocambio extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'destinocambio';

    protected $primaryKey = 'iddestinocambio';

    public $timestamps = false;

    protected $fillable = [
        'estado', 'nombre',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Destinocambio
     * ✅ Usa PK_DESTINOCAMBIO (indexado)
     */
    public function destinocambio()
    {
        return $this->belongsTo(Destinocambio::class, 'iddestinocambio', 'iddestinocambio');
    }
}
