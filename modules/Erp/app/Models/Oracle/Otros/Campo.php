<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CAMPO
 *
 * ÍNDICES DISPONIBLES:
 * PK_CAMPO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCAMPO
 */
class Campo extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'campo';

    protected $primaryKey = 'idcampo';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idtipo', 'idtabla', 'estado', 'idusuariomod', 'codigo',
        'longitud', 'decimales', 'clave', 'descripcion',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Campo
     * ✅ Usa PK_CAMPO (indexado)
     */
    public function campo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Campo::class, 'idcampo', 'idcampo');
    }

    /**
     * Relación: Tabla
     * ⚠️  SIN ÍNDICE en IDTABLA
     */
    public function tabla()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Tabla::class, 'idtabla', 'idtabla');
    }
}
