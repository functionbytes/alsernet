<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TTARJETAREGALO
 *
 * ÍNDICES DISPONIBLES:
 * PK_IDTTARJETAREGALO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTTARJETAREGALO
 */
class Ttarjetaregalo extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'ttarjetaregalo';

    protected $primaryKey = 'idttarjetaregalo';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaja', 'estado', 'descripcion',
        'importe', 'not',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Ttarjetaregalo
     * ✅ Usa PK_IDTTARJETAREGALO (indexado)
     */
    public function ttarjetaregalo()
    {
        return $this->belongsTo(Ttarjetaregalo::class, 'idttarjetaregalo', 'idttarjetaregalo');
    }
}
