<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_PAISES
 *
 * ÍNDICES DISPONIBLES:
 * PK_W_PAISES (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WPaises extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_paises';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'nombre', 'zona', 'mostrar_solo', 'orden', 'iva',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con WPaisesIdiomas
     */
    public function wPaisesIdiomas()
    {
        return $this->hasMany(WPaisesIdiomas::class, 'id_pais', 'idw_paises');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_PAISES (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id', 'id');
    }
}
