<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Configuracion\Pais;
use Modules\Erp\Models\Oracle\Lote\Tarifalote;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TTARIFA
 *
 * ÍNDICES DISPONIBLES:
 * PK_TTARIFA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTTARIFA
 */
class Ttarifa extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'ttarifa';

    protected $primaryKey = 'idttarifa';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'estado', 'idusuariomod', 'descripcion',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con Pais
     */
    public function pais()
    {
        return $this->hasMany(Pais::class, 'idttarifa', 'idttarifa');
    }

    /**
     * Relación inversa con Tarifalote
     */
    public function tarifalotes()
    {
        return $this->hasMany(Tarifalote::class, 'idttarifa', 'idttarifa');
    }

    /**
     * Relación: Ttarifa
     * ✅ Usa PK_TTARIFA (indexado)
     */
    public function ttarifa()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Ttarifa::class, 'idttarifa', 'idttarifa');
    }
}
