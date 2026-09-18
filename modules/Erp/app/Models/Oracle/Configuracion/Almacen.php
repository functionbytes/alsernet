<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Albaran\SeriealbarancliCapthaya;
use Modules\Erp\Models\Oracle\Cliente\Cliente;
use Modules\Erp\Models\Oracle\Otros\Empresa;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ALMACEN (Almacenes/Delegaciones)
 *
 * @property int $idalmacen Clave primaria (PK)
 * @property string $descripcion Nombre del almacén
 * @property string $alias
 * @property int $estado
 *
 * ÍNDICES DISPONIBLES:
 * PK_ALMACEN (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALMACEN
 */
class Almacen extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'almacen';

    protected $primaryKey = 'idalmacen';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'estado', 'congelar', 'idempresa', 'direccion',
        'telefono1', 'telefono2', 'rutaentrada', 'rutasalida', 'rutabd',
        'idtalmacen', 'localidad', 'idusuariomod', 'porreparto', 'idseriealbarancli',
        'idcliente', 'idsubcuentaventa', 'idsubcuentacompra', 'alias_delegacion', 'cp',
        'provincia', 'pais', 'prioridad_pedir_a_tienda', 'alias', 'tiene_etiquetas_electronicas',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'congelar' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con Contrasena
     */
    public function contrasenas()
    {
        return $this->hasMany(Contrasena::class, 'idalmacen', 'idalmacen');
    }

    /**
     * Relación inversa con Transportista
     */
    public function transportistas()
    {
        return $this->hasMany(Transportista::class, 'idalmacen', 'idalmacen');
    }

    /**
     * Relación: Almacen
     * ✅ Usa PK_ALMACEN (indexado)
     */
    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'idalmacen', 'idalmacen');
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
     * Relación: Talmacen
     * ⚠️  SIN ÍNDICE en IDTALMACEN
     */
    public function talmacen()
    {
        return $this->belongsTo(Talmacen::class, 'idtalmacen', 'idtalmacen');
    }

    /**
     * Relación: Seriealbarancli
     * ⚠️  SIN ÍNDICE en IDSERIEALBARANCLI
     */
    public function seriealbarancli()
    {
        return $this->belongsTo(SeriealbarancliCapthaya::class, 'idseriealbarancli', 'idseriealbarancli');
    }

    /**
     * Relación: Cliente
     * ⚠️  SIN ÍNDICE en IDCLIENTE
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idcliente', 'idcliente');
    }
}
