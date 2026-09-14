<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketLink extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_links';

    protected $fillable = [
        'ticket_id',
        'linked_ticket_id',
        'link_type',
        'created_by',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function linkedTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'linked_ticket_id');
    }
}
