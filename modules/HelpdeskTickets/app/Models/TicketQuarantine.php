<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Correo entrante retenido por el clasificador de spam, a la espera de que
 * alguien lo revise. Ver la migración para el porqué de retener en vez de
 * descartar.
 */
class TicketQuarantine extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_RELEASED = 'released';

    public const STATUS_CONFIRMED = 'confirmed';

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_quarantine';

    protected $fillable = [
        'from_email',
        'from_name',
        'subject',
        'body_text',
        'body_html',
        'message_id',
        'spam_score',
        'reason',
        'status',
        'released_ticket_id',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'spam_score' => 'float',
            'reviewed_at' => 'datetime',
        ];
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
