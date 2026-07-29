<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla OBJETO
 *
 * ÍNDICES DISPONIBLES:
 * PK_OBJETO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDOBJETO
 */
class Objeto extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'objeto';

    protected $primaryKey = 'idobjeto';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'estado', 'idusuariomod', 'nombre', 'descripcion', 'tipo',
        'padre', 'doc_ayuda',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Objeto
     * ✅ Usa PK_OBJETO (indexado)
     */
    public function objeto()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Objeto::class, 'idobjeto', 'idobjeto');
    }
}
