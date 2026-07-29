<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LIMPORTACION_ARTICULO_EXT
 *
 * ÍNDICES DISPONIBLES:
 * PK_LIMPORTACION_ARTICULO_EXT (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLIMPORTACION_ARTICULO_EXT
 */
class LimportacionArticuloExt extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'limportacion_articulo_ext';

    protected $primaryKey = 'idlimportacion_articulo_ext';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idimportacion_articulo', 'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado',
        'seleccionado', 'fprocesado', 'idartiprov',
    ];

    protected $casts = [
        'fprocesado' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: LimportacionArticuloExt
     * ✅ Usa PK_LIMPORTACION_ARTICULO_EXT (indexado)
     */
    public function limportacionArticuloExt()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\LimportacionArticuloExt::class, 'idlimportacion_articulo_ext', 'idlimportacion_articulo_ext');
    }

    /**
     * Relación: ImportacionArticulo
     * ⚠️  SIN ÍNDICE en IDIMPORTACION_ARTICULO
     */
    public function importacionArticulo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\ImportacionArticulo::class, 'idimportacion_articulo', 'idimportacion_articulo');
    }

    /**
     * Relación: Artiprov
     * ⚠️  SIN ÍNDICE en IDARTIPROV
     */
    public function artiprov()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Proveedor\Artiprov::class, 'idartiprov', 'idartiprov');
    }
}
