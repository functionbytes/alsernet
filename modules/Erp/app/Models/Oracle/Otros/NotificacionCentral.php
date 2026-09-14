<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla NOTIFICACION_CENTRAL
 *
 * ÍNDICES DISPONIBLES:
 * PK_NOTIFICACION_CENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDNOTIFICACION_CENTRAL
 */
class NotificacionCentral extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'notificacion_central';

    protected $primaryKey = 'idnotificacion_central';

    public $timestamps = false;

    protected $fillable = [
        'tipo', 'fecha', 'parametros', 'origen',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];
}
