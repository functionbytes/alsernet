<?php

namespace Modules\Erp\Models\Oracle\Lote;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Models\Oracle\Articulo\Articulo;
use Modules\Erp\Models\Oracle\Configuracion\Tipomedida;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LLLOTE
 *
 * ÍNDICES DISPONIBLES:
 * PK_LLLOTE (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLLLOTE
 */
class Lllote extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lllote';

    protected $primaryKey = 'idlllote';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    protected $fillable = [
        'idllote', 'idarticulo', 'estado', 'unidades', 'idusuariocre',
        'idusuariomod', 'idtipomedida', 'unid',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Lllote
     * ✅ Usa PK_LLLOTE (indexado)
     */
    public function lllote()
    {
        return $this->belongsTo(Lllote::class, 'idlllote', 'idlllote');
    }

    /**
     * Relación: Llote
     * ⚠️  SIN ÍNDICE en IDLLOTE
     */
    public function llote()
    {
        return $this->belongsTo(Llote::class, 'idllote', 'idllote');
    }

    /**
     * Relación: Articulo
     * ⚠️  SIN ÍNDICE en IDARTICULO
     */
    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Relación: Tipomedida
     * ⚠️  SIN ÍNDICE en IDTIPOMEDIDA
     */
    public function tipomedida()
    {
        return $this->belongsTo(Tipomedida::class, 'idtipomedida', 'idtipomedida');
    }
}
