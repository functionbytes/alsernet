<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CAMBIOTABLAMETATABLA
 *
 * ÍNDICES DISPONIBLES:
 * ⚠️  PC_CAMBIOTABLAMETATABLA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCAMBIOTABLAMETATABLA
 */
class Cambiotablametatabla extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'cambiotablametatabla';

    protected $primaryKey = 'idcambiotablametatabla';

    public $timestamps = false;

    protected $fillable = [
        'nombrereal', 'nombreficticio', 'campoclave',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Cambiotablametatabla
     * ✅ Usa PC_CAMBIOTABLAMETATABLA (indexado)
     */
    public function cambiotablametatabla()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Cambiotablametatabla::class, 'idcambiotablametatabla', 'idcambiotablametatabla');
    }
}
