<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TABLA
 *
 * ÍNDICES DISPONIBLES:
 * PK_TABLA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTABLA
 */
class Tabla extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tabla';

    protected $primaryKey = 'idtabla';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'estado', 'idusuariomod', 'idcampo', 'cam_idcampo', 'codigo',
        'descripcion', 'tipo', 'cadena_filtro', 'descrip_singular', 'genero',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Tabla
     * ✅ Usa PK_TABLA (indexado)
     */
    public function tabla()
    {
        return $this->belongsTo(Tabla::class, 'idtabla', 'idtabla');
    }

    /**
     * Relación: Campo
     * ⚠️  SIN ÍNDICE en IDCAMPO
     */
    public function campo()
    {
        return $this->belongsTo(Campo::class, 'idcampo', 'idcampo');
    }
}
