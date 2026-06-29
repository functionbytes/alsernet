<?php

namespace Modules\Erp\Models\Oracle\Factura;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Configuracion\Almacen;
use Modules\Erp\Models\Oracle\Configuracion\Tipodiario;
use Modules\Erp\Models\Oracle\Otros\Empresa;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla SERIEFACTURAPRO
 *
 * ÍNDICES DISPONIBLES:
 * PK_SERIEFACTURAPRO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSERIEFACTURAPRO
 */
class Seriefacturapro extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'seriefacturapro';

    protected $primaryKey = 'idseriefacturapro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'descripcorta', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
        'estado', 'numero', 'idempresa', 'nseriecontaplus', 'fdesde',
        'fhasta', 'idtipodiario', 'idalmacen',
    ];

    protected $casts = [
        'fdesde' => 'datetime',
        'fhasta' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con Facturapro
     */
    public function facturapros()
    {
        return $this->hasMany(Facturapro::class, 'idseriefacturapro', 'idseriefacturapro');
    }

    /**
     * Relación: Seriefacturapro
     * ✅ Usa PK_SERIEFACTURAPRO (indexado)
     */
    public function seriefacturapro()
    {
        return $this->belongsTo(Seriefacturapro::class, 'idseriefacturapro', 'idseriefacturapro');
    }

    /**
     * Relación: Empresa
     * ⚠️  SIN ÍNDICE en IDEMPRESA
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'idempresa', 'idempresa');
    }

    /**
     * Relación: Tipodiario
     * ⚠️  SIN ÍNDICE en IDTIPODIARIO
     */
    public function tipodiario()
    {
        return $this->belongsTo(Tipodiario::class, 'idtipodiario', 'idtipodiario');
    }

    /**
     * Relación: Almacen
     * ⚠️  SIN ÍNDICE en IDALMACEN
     */
    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'idalmacen', 'idalmacen');
    }
}
