<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Configuracion\Pais;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla PREFIJO_TELEFONO
 *
 * ÍNDICES DISPONIBLES:
 * PK_PREFIJO_TELEFONO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPREFIJO_TELEFONO
 */
class PrefijoTelefono extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'prefijo_telefono';

    protected $primaryKey = 'idprefijo_telefono';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'estado', 'prefijo', 'idpais',
        'idpasarela_sms',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: PrefijoTelefono
     * ✅ Usa PK_PREFIJO_TELEFONO (indexado)
     */
    public function prefijoTelefono()
    {
        return $this->belongsTo(PrefijoTelefono::class, 'idprefijo_telefono', 'idprefijo_telefono');
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
