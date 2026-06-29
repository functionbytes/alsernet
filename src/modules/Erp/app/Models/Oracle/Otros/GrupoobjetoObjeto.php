<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla GRUPOOBJETO_OBJETO
 *
 * ÍNDICES DISPONIBLES:
 * PK_GRUPOOBJETO_OBJETO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDGRUPOOBJETO, IDOBJETO
 */
class GrupoobjetoObjeto extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'grupoobjeto_objeto';

    protected $primaryKey = 'idgrupoobjeto';

    public $timestamps = false;

    protected $fillable = [
        'idobjeto',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Grupoobjeto
     * ✅ Usa PK_GRUPOOBJETO_OBJETO (indexado)
     */
    public function grupoobjeto()
    {
        return $this->belongsTo(GrupoObjetos::class, 'idgrupoobjeto', 'idgrupoobjeto');
    }

    /**
     * Relación: Objeto
     * ⚠️  SIN ÍNDICE en IDOBJETO
     */
    public function objeto()
    {
        return $this->belongsTo(Objeto::class, 'idobjeto', 'idobjeto');
    }
}
