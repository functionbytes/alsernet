<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ZONA_POSTAL
 *
 * ÍNDICES DISPONIBLES:
 * PK_ZONA_POSTAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDZONA_POSTAL
 */
class ZonaPostal extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'zona_postal';

    protected $primaryKey = 'idzona_postal';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: ZonaPostal
     * ✅ Usa PK_ZONA_POSTAL (indexado)
     */
    public function zonaPostal()
    {
        return $this->belongsTo(ZonaPostal::class, 'idzona_postal', 'idzona_postal');
    }
}
