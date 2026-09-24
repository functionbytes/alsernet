<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Borrador del composer de un agente en un ticket (uno por pareja).
 */
class TicketDraft extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_drafts';

    protected $fillable = ['ticket_id', 'user_id', 'body', 'mode'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
