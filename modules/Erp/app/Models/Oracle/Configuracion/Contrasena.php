<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CONTRASENA
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_CONTRASENA_IDALMACEN (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALMACEN
 *
 * PK_CONTRASENA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCONTRASENA
 *
 * ⚠️  UK_CONTRASENA_CODIGO_ALMACEN (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: CODIGO, IDALMACEN
 */
class Contrasena extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'contrasena';

    protected $primaryKey = 'idcontrasena';

    public $timestamps = false;

    protected $fillable = [
        'descripcion', 'texto', 'valor', 'idalmacen', 'codigo',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Almacen
     */
    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'idalmacen', 'idalmacen');
    }

    /**
     * Relación: Contrasena
     * ✅ Usa PK_CONTRASENA (indexado)
     */
    public function contrasena()
    {
        return $this->belongsTo(Contrasena::class, 'idcontrasena', 'idcontrasena');
    }
}
