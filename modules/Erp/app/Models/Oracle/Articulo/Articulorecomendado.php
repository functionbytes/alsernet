<?php

namespace Modules\Erp\Models\Oracle\Articulo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ARTICULORECOMENDADO
 *
 * ÍNDICES DISPONIBLES:
 * PK_ARTICULORECOMENDADO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTICULORECOMENDADO
 */
class Articulorecomendado extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'articulorecomendado';

    protected $primaryKey = 'idarticulorecomendado';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idarticulo', 'idarticulorec', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Articulorecomendado
     * ✅ Usa PK_ARTICULORECOMENDADO (indexado)
     */
    public function articulorecomendado()
    {
        return $this->belongsTo(Articulorecomendado::class, 'idarticulorecomendado', 'idarticulorecomendado');
    }

    /**
     * Relación: Articulo
     * ⚠️  SIN ÍNDICE en IDARTICULO
     */
    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'idarticulo', 'idarticulo');
    }
}
