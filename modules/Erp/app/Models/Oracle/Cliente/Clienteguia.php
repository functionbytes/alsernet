<?php

namespace Modules\Erp\Models\Oracle\Cliente;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CLIENTEGUIA
 *
 * ÍNDICES DISPONIBLES:
 * PK_CLIENTEGUIA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTEGUIA
 */
class Clienteguia extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'clienteguia';

    protected $primaryKey = 'idclienteguia';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcliente', 'descripcion', 'fguia', 'nguia', 'narma',
        'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
    ];

    protected $casts = [
        'fguia' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Clienteguia
     * ✅ Usa PK_CLIENTEGUIA (indexado)
     */
    public function clienteguia()
    {
        return $this->belongsTo(Clienteguia::class, 'idclienteguia', 'idclienteguia');
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
