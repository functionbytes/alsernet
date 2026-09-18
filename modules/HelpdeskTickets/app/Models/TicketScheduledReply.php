<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskTickets\Models\Concerns\BelongsToHelpdeskUser;

class TicketScheduledReply extends Model
{
    use BelongsToHelpdeskUser;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_scheduled_replies';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'body',
        'is_internal',
        'deliver_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'deliver_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsToHelpdeskUser('user_id', 'user');
    }
}
