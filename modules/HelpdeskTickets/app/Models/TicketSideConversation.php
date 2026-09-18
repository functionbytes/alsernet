<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HelpdeskTickets\Models\Concerns\BelongsToHelpdeskUser;

class TicketSideConversation extends Model
{
    use BelongsToHelpdeskUser;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_side_conversations';

    protected $fillable = [
        'ticket_id',
        'subject',
        'participant_type',
        'participant_email',
        'participant_user_id',
        'status',
        'created_by',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketSideConversationMessage::class, 'side_conversation_id')->oldest();
    }

    public function participantUser(): BelongsTo
    {
        return $this->belongsToHelpdeskUser('participant_user_id', 'participantUser');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsToHelpdeskUser('created_by', 'creator');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }
}
