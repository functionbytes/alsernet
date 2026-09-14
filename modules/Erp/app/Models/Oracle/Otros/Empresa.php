<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla EMPRESA
 *
 * ÍNDICES DISPONIBLES:
 * PK_EMPRESA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDEMPRESA
 */
class Empresa extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'empresa';

    protected $primaryKey = 'idempresa';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'nombre', 'cif', 'calle', 'num', 'localidad',
        'cp', 'provincia', 'pais', 'estado', 'telefono1',
        'telefono2', 'fax', 'observaciones', 'idusuariomod', 'datosregistrales',
        'email', 'web', 'logo', 'idv_conexion_sii', 'saftpt_nif',
        'saftpt_nombre', 'saftpt_direccion', 'saftpt_localidad', 'saftpt_cp',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Empresa
     * ✅ Usa PK_EMPRESA (indexado)
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'idempresa', 'idempresa');
    }
}
