<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LZONA_POSTAL
 *
 * ÍNDICES DISPONIBLES:
 * PK_LZONA_POSTAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLZONA_POSTAL
 */
class LzonaPostal extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lzona_postal';

    protected $primaryKey = 'idlzona_postal';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idzona_postal', 'cp', 'estado', 'idusuariocre', 'idusuariomod',
        'idusuariobaj', 'idpais',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: LzonaPostal
     * ✅ Usa PK_LZONA_POSTAL (indexado)
     */
    public function lzonaPostal()
    {
        return $this->belongsTo(LzonaPostal::class, 'idlzona_postal', 'idlzona_postal');
    }

    /**
     * Relación: ZonaPostal
     * ⚠️  SIN ÍNDICE en IDZONA_POSTAL
     */
    public function zonaPostal()
    {
        return $this->belongsTo(ZonaPostal::class, 'idzona_postal', 'idzona_postal');
    }

    /**
     * Relación: Pais
     * ⚠️  SIN ÍNDICE en IDPAIS
     */
    public function pais()
    {
        return $this->belongsTo(Pais::class, 'idpais', 'idpais');
    }
}
