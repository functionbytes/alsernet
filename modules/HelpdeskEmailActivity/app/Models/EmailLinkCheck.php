<?php

namespace Modules\HelpdeskEmailActivity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una URL comprobada dentro de una ejecución de la pestaña "Enlaces".
 *
 * Sin timestamps de Eloquent: la única fecha que importa es cuándo se pidió la
 * URL (`checked_at`), y un `updated_at` sugeriría que estas filas se editan —
 * no se editan nunca, cada comprobación añade filas nuevas para conservar el
 * histórico.
 */
class EmailLinkCheck extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email_log_id',
        'url',
        'url_hash',
        'status_code',
        'status',
        'checked_by',
        'checked_at',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'checked_at' => 'datetime',
    ];

    public function emailLog(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class);
    }

    /**
     * Hash de la URL para poder indexarla: en MySQL con utf8mb4 un índice sobre
     * la columna completa no cabe, y una URL con parámetros los supera a menudo.
     */
    public static function hashUrl(string $url): string
    {
        return hash('sha256', $url);
    }

    /**
     * ¿Respondió mal? 0 es "no se pidió" (enlace de seguimiento propio) y no
     * cuenta como error; -1 es un fallo de conexión y sí.
     */
    public function failed(): bool
    {
        return $this->status_code >= 400 || $this->status_code === -1;
    }
}
