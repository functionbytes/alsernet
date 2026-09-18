<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla PARAMETRO
 *
 * ÍNDICES DISPONIBLES:
 * PK_PARAMETRO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPARAMETRO
 */
class Parametro extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'parametro';

    protected $primaryKey = 'idparametro';

    public $timestamps = false;

    protected $fillable = [
        'idgrupo_parametro', 'descripcion', 'valor', 'tipo', 'seguridad',
        'grupo', 'clave', 'borrado', 'traduccion',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Parametro
     * ✅ Usa PK_PARAMETRO (indexado)
     */
    public function parametro()
    {
        return $this->belongsTo(Parametro::class, 'idparametro', 'idparametro');
    }
}
