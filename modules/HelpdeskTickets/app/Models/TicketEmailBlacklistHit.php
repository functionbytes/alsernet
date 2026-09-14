<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un evento individual de bloqueo por lista negra (un correo entrante
 * descartado por TicketEmailBlacklist::matches()). Ver la migración para el
 * porqué de esta tabla frente a matched_count/last_matched_at.
 */
class TicketEmailBlacklistHit extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_email_blacklist_hits';

    protected $fillable = [
        'blacklist_id',
        'from_email',
        'subject',
        'body_html',
        'body_text',
    ];

    public function blacklist(): BelongsTo
    {
        return $this->belongsTo(TicketEmailBlacklist::class, 'blacklist_id');
    }
}
