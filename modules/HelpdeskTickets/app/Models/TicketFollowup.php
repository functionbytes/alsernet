<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskTickets\Models\Concerns\BelongsToHelpdeskUser;

class TicketFollowup extends Model
{
    use BelongsToHelpdeskUser;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_followups';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'scheduled_at',
        'note',
        'is_sent',
        'sent_at',
        'step',
        'cancel_if_customer_replies',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'is_sent' => 'boolean',
            'cancel_if_customer_replies' => 'boolean',
            'step' => 'integer',
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

    /**
     * Pasos que siguen vivos: ni enviados ni cancelados.
     */
    public function scopePending($query)
    {
        return $query->where('is_sent', false)->whereNull('cancelled_at');
    }
}
