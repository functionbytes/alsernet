<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_TIENDAS
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_W_TIENDAS_ID_PADRE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_PADRE
 *
 * PK_W_TIENDAS (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WTiendas extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_tiendas';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id_padre', 'nombre', 'activo', 'orden',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con WTiendas
     */
    public function _padre()
    {
        return $this->belongsTo(WTiendas::class, 'id_padre', 'idw_tiendas');
    }

    /**
     * Relación inversa con WDescuentosRelacionados
     */
    public function wDescuentosRelacionados()
    {
        return $this->hasMany(WDescuentosRelacionados::class, 'id_tienda', 'idw_tiendas');
    }

    /**
     * Relación inversa con WTiendas
     */
    public function wTiendas()
    {
        return $this->hasMany(WTiendas::class, 'id_padre', 'idw_tiendas');
    }

    /**
     * Relación inversa con WTiendasIdiomas
     */
    public function wTiendasIdiomas()
    {
        return $this->hasMany(WTiendasIdiomas::class, 'id_tienda', 'idw_tiendas');
    }

    /**
     * Relación: Padre
     * ✅ Usa IDX_W_TIENDAS_ID_PADRE (indexado)
     */
    public function padre()
    {
        return $this->belongsTo(WTiendas::class, 'id_padre', 'id');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_TIENDAS (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(WAyudas::class, 'id', 'id');
    }
}
