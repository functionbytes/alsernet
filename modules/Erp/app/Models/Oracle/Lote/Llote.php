<?php

namespace Modules\Erp\Models\Oracle\Lote;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LLOTE
 *
 * ÍNDICES DISPONIBLES:
 * PK_LLOTE (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLLOTE
 */
class Llote extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'llote';

    protected $primaryKey = 'idllote';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    protected $fillable = [
        'idlote', 'estado', 'idusuariocre', 'idusuariomod', 'descripcion',
        'precio',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con Lloteidioma
     */
    public function lloteidiomas()
    {
        return $this->hasMany(Lloteidioma::class, 'idllote', 'idllote');
    }

    /**
     * Relación: Llote
     * ✅ Usa PK_LLOTE (indexado)
     */
    public function llote()
    {
        return $this->belongsTo(Llote::class, 'idllote', 'idllote');
    }

    /**
     * Relación: Lote
     * ⚠️  SIN ÍNDICE en IDLOTE
     */
    public function lote()
    {
        return $this->belongsTo(Lote::class, 'idlote', 'idlote');
    }
}
