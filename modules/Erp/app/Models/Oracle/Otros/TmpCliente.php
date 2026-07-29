<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TMP_CLIENTE
 */
class TmpCliente extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tmp_cliente';

    protected $primaryKey = 'idcliente';

    public $timestamps = false;

    protected $fillable = [
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Cliente
     * ⚠️  SIN ÍNDICE en IDCLIENTE
     */
    public function cliente()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cliente\Cliente::class, 'idcliente', 'idcliente');
    }
}
