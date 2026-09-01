<?php

namespace Modules\HelpdeskEmailLog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un enlace único reescrito dentro del cuerpo de un envío (ver
 * LogEmailQueued::injectClickTracking) — el token es lo que viaja en el
 * correo real; la URL original solo vive en esta tabla, nunca en el token.
 */
class EmailLogLink extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email_log_id',
        'token',
        'url',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function emailLog(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(EmailLogClick::class);
    }
}
