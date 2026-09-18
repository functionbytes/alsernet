<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla MANTFIL
 *
 * ÍNDICES DISPONIBLES:
 * PK_MANTFIL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDMANTFIL
 */
class Mantfil extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'mantfil';

    protected $primaryKey = 'idmantfil';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idmfiltro', 'idobjeto', 'estado', 'idusuariomod', 'permitefiltro',
        'orden',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Mantfil
     * ✅ Usa PK_MANTFIL (indexado)
     */
    public function mantfil()
    {
        return $this->belongsTo(Mantfil::class, 'idmantfil', 'idmantfil');
    }

    /**
     * Relación: Mfiltro
     * ⚠️  SIN ÍNDICE en IDMFILTRO
     */
    public function mfiltro()
    {
        return $this->belongsTo(Mfiltro::class, 'idmfiltro', 'idmfiltro');
    }

    /**
     * Relación: Objeto
     * ⚠️  SIN ÍNDICE en IDOBJETO
     */
    public function objeto()
    {
        return $this->belongsTo(Objeto::class, 'idobjeto', 'idobjeto');
    }
}
